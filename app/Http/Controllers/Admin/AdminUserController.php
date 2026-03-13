<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            match ($request->status) {
                'admin' => $query->where('role', 'admin'),
                'active' => $query->where('role', '!=', 'admin')
                    ->where('subscription_expires_at', '>', now()),
                'expired' => $query->where('role', '!=', 'admin')
                    ->where(function ($q) {
                        $q->whereNull('subscription_expires_at')
                          ->orWhere('subscription_expires_at', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('trial_ends_at')
                          ->orWhere('trial_ends_at', '<=', now());
                    }),
                default => null,
            };
        }

        $users = $query->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'subscription_status' => $user->subscriptionStatus(),
                'subscription_expires_at' => $user->subscription_expires_at?->toISOString(),
                'trial_ends_at' => $user->trial_ends_at?->toISOString(),
                'days_remaining' => $user->daysRemaining(),
                'created_at' => $user->created_at->toISOString(),
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function calendar(Request $request): Response
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
        $endOfMonth = $startOfMonth->copy()->endOfMonth()->endOfDay();

        // Users with subscription expiring this month (non-admin)
        $subscriptionUsers = User::where('role', '!=', 'admin')
            ->whereBetween('subscription_expires_at', [$startOfMonth, $endOfMonth])
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'date' => $user->subscription_expires_at->toDateString(),
                'type' => 'subscription',
            ]);

        // Users with trial ending this month (non-admin, without active subscription)
        $trialUsers = User::where('role', '!=', 'admin')
            ->whereBetween('trial_ends_at', [$startOfMonth, $endOfMonth])
            ->where(function ($q) {
                $q->whereNull('subscription_expires_at')
                  ->orWhere('subscription_expires_at', '<=', now());
            })
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'date' => $user->trial_ends_at->toDateString(),
                'type' => 'trial',
            ]);

        $allUsers = $subscriptionUsers->merge($trialUsers);

        $expirationsByDate = $allUsers->groupBy('date')->map(fn ($users) => $users->values())->toArray();

        return Inertia::render('Admin/Users/Calendar', [
            'expirationsByDate' => $expirationsByDate,
            'month' => $month,
            'year' => $year,
            'totalExpiring' => $allUsers->count(),
        ]);
    }

    public function show(User $user): Response
    {
        return Inertia::render('Admin/Users/Show', [
            'userData' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'subscription_status' => $user->subscriptionStatus(),
                'subscription_expires_at' => $user->subscription_expires_at?->toISOString(),
                'trial_ends_at' => $user->trial_ends_at?->toISOString(),
                'days_remaining' => $user->daysRemaining(),
                'created_at' => $user->created_at->toISOString(),
                'invoices_count' => $user->invoices()->count(),
                'clients_count' => $user->clients()->count(),
                'agency' => $user->agency?->name,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'subscription_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        if (!empty($validated['subscription_days']) && $validated['subscription_days'] > 0) {
            $user->update([
                'subscription_expires_at' => now()->addDays($validated['subscription_days']),
            ]);
        }

        return back()->with('success', __('admin.user_created'));
    }

    public function extend(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $days = $validated['days'];

        $base = $user->subscription_expires_at && $user->subscription_expires_at->isFuture()
            ? $user->subscription_expires_at
            : now();

        $user->update([
            'subscription_expires_at' => $base->addDays($days),
        ]);

        return back()->with('success', __('admin.subscription_extended', ['days' => $days]));
    }

    public function revoke(User $user): RedirectResponse
    {
        $user->update([
            'subscription_expires_at' => null,
            'trial_ends_at' => null,
        ]);

        return back()->with('success', __('admin.subscription_revoked'));
    }

    public function toggleAdmin(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', __('admin.cannot_demote_self'));
        }

        $newRole = $user->role === 'admin' ? 'user' : 'admin';
        $user->update(['role' => $newRole]);

        $message = $newRole === 'admin'
            ? __('admin.user_promoted')
            : __('admin.user_demoted');

        return back()->with('success', $message);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', __('admin.cannot_delete_self'));
        }

        $user->delete();

        return back()->with('success', __('admin.user_deleted'));
    }

    public function impersonate(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', __('admin.cannot_impersonate_self'));
        }

        $request->session()->put('impersonating_from', $request->user()->id);
        Auth::login($user);

        return redirect()->route('dashboard');
    }

    public static function stopImpersonating(Request $request): RedirectResponse
    {
        $adminId = $request->session()->pull('impersonating_from');

        if ($adminId) {
            Auth::login(User::findOrFail($adminId));
        }

        return redirect()->route('admin.dashboard');
    }
}

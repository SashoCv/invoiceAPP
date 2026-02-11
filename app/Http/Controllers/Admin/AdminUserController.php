<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                'trial' => $query->where('role', '!=', 'admin')
                    ->whereNull('subscription_expires_at')
                    ->where('trial_ends_at', '>', now()),
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
}

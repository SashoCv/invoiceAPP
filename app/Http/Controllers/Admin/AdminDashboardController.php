<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function index(): Response
    {
        $totalUsers = User::where('role', '!=', 'admin')->count();

        $activeSubscriptions = User::where('role', '!=', 'admin')
            ->where(function ($q) {
                $q->where('subscription_expires_at', '>', now())
                  ->orWhere('trial_ends_at', '>', now());
            })
            ->count();

        $expiredUsers = $totalUsers - $activeSubscriptions;

        $newThisMonth = User::where('role', '!=', 'admin')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $trialUsers = User::where('role', '!=', 'admin')
            ->whereNull('subscription_expires_at')
            ->where('trial_ends_at', '>', now())
            ->count();

        $recentUsers = User::where('role', '!=', 'admin')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'subscription_status' => $user->subscriptionStatus(),
                'days_remaining' => $user->daysRemaining(),
                'created_at' => $user->created_at->toISOString(),
            ]);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'totalUsers' => $totalUsers,
                'activeSubscriptions' => $activeSubscriptions,
                'expiredUsers' => $expiredUsers,
                'newThisMonth' => $newThisMonth,
                'trialUsers' => $trialUsers,
            ],
            'recentUsers' => $recentUsers,
        ]);
    }
}

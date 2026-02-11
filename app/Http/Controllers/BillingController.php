<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Billing/Index', [
            'subscriptionStatus' => $user->subscriptionStatus(),
            'isActive' => $user->hasActiveSubscription(),
            'expiresAt' => $user->subscription_expires_at?->toISOString(),
            'trialEndsAt' => $user->trial_ends_at?->toISOString(),
            'daysRemaining' => $user->daysRemaining(),
        ]);
    }
}

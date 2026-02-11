<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSubscribed
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasActiveSubscription()) {
            return redirect()->route('billing.index')
                ->with('error', __('subscription.expired_banner'));
        }

        return $next($request);
    }
}

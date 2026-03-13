<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'isAdmin' => $user?->isAdmin() ?? false,
                'subscription' => $user ? [
                    'status' => $user->subscriptionStatus(),
                    'isActive' => $user->hasActiveSubscription(),
                    'expiresAt' => $user->subscription_expires_at?->toISOString(),
                    'daysRemaining' => $user->daysRemaining(),
                ] : null,
            ],
            'impersonating' => $request->session()->has('impersonating_from'),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'locale' => app()->getLocale(),
            'translations' => fn () => $this->getTranslations(),
        ];
    }

    protected function getTranslations(): array
    {
        $locale = app()->getLocale();

        return cache()->rememberForever("translations_{$locale}", function () use ($locale) {
            $path = lang_path($locale);
            $translations = [];
            foreach (glob($path . '/*.php') as $file) {
                $key = basename($file, '.php');
                $translations[$key] = require $file;
            }
            return $translations;
        });
    }
}

import { usePage } from '@inertiajs/react';
import type { PageProps } from '@/types';

export function useSubscription() {
    const { auth } = usePage<PageProps>().props;

    return {
        isActive: auth.subscription?.isActive ?? false,
        status: auth.subscription?.status ?? 'expired',
        isAdmin: auth.isAdmin,
        expiresAt: auth.subscription?.expiresAt ?? null,
        daysRemaining: auth.subscription?.daysRemaining ?? null,
    };
}

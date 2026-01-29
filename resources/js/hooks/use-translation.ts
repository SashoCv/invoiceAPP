import { usePage } from '@inertiajs/react';
import type { PageProps } from '@/types';

export function useTranslation() {
    const { translations, locale } = usePage<PageProps>().props;

    const t = (key: string, replacements: Record<string, string> = {}): string => {
        const [namespace, ...rest] = key.split('.');
        const translationKey = rest.join('.');

        let translation = translations?.[namespace]?.[translationKey] || key;

        Object.entries(replacements).forEach(([search, replace]) => {
            translation = translation.replace(`:${search}`, replace);
        });

        return translation;
    };

    return { t, locale };
}

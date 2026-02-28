import { PropsWithChildren } from 'react';
import { Link, usePage } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { cn } from '@/lib/utils';
import { useTranslation } from '@/hooks/use-translation';
import { User, Building2, CreditCard, FileCode, Wallet } from 'lucide-react';

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { t } = useTranslation();
    const currentPath = usePage().url;

    const tabs = [
        { name: t('navigation.profile'), href: '/settings/profile', icon: User },
        { name: t('navigation.agency'), href: '/settings/agency', icon: Building2 },
        { name: t('navigation.bank_accounts'), href: '/settings/bank-accounts', icon: CreditCard },
        { name: t('navigation.templates'), href: '/settings/templates', icon: FileCode },
        { name: t('navigation.billing'), href: '/billing', icon: Wallet },
    ];

    return (
        <AppLayout>
            <div>
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-900">{t('navigation.section_settings')}</h1>
                </div>

                <div className="flex border-b border-gray-200 mb-6 -mt-2 overflow-x-auto">
                    {tabs.map((tab) => {
                        const isActive = tab.href === '/billing'
                            ? currentPath === '/billing'
                            : currentPath.startsWith(tab.href);
                        return (
                            <Link
                                key={tab.href}
                                href={tab.href}
                                className={cn(
                                    'flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 whitespace-nowrap transition-colors',
                                    isActive
                                        ? 'border-blue-600 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                )}
                            >
                                <tab.icon className="w-4 h-4" />
                                {tab.name}
                            </Link>
                        );
                    })}
                </div>

                {children}
            </div>
        </AppLayout>
    );
}

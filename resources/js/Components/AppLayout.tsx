import { useState, useEffect, useRef, PropsWithChildren } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { useTranslation } from '@/hooks/use-translation';
import { useToast } from '@/hooks/use-toast';
import { Toaster } from '@/Components/ui/toaster';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import CurrencyCalculator from '@/Components/CurrencyCalculator';
import FynvoLogo from '@/Components/FynvoLogo';
import {
    LayoutDashboard,
    FileText,
    FileCheck,
    ClipboardList,
    Users,
    Package,
    Receipt,
    Landmark,
    Warehouse,
    ClipboardCheck,
    PackageMinus,
    TrendingUp,
    Settings,
    LogOut,
    Menu,
    X,
    Calculator,
    Shield,
    AlertTriangle,
} from 'lucide-react';
import { formatDate } from '@/lib/utils';
import type { PageProps } from '@/types';

interface NavItem {
    name: string;
    href: string;
    icon: React.ElementType;
    active: boolean;
}

interface NavSection {
    label?: string;
    items: NavItem[];
}

export default function AppLayout({ children }: PropsWithChildren) {
    const { t, locale } = useTranslation();
    const { auth, flash, impersonating } = usePage<PageProps>().props;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [calcOpen, setCalcOpen] = useState(false);
    const { toast } = useToast();
    const currentPath = usePage().url;

    const shownFlashRef = useRef<string | null>(null);

    useEffect(() => {
        const flashMsg = flash?.success || flash?.error || null;
        if (!flashMsg) {
            shownFlashRef.current = null;
            return;
        }
        if (flashMsg === shownFlashRef.current) return;
        shownFlashRef.current = flashMsg;

        if (flash?.success) {
            toast({
                title: t('toast.success'),
                description: flash.success,
                variant: 'success',
            });
        }
        if (flash?.error) {
            toast({
                title: t('toast.error'),
                description: flash.error,
                variant: 'destructive',
            });
        }
    }, [flash]);

    const navSections: NavSection[] = [
        {
            items: [
                {
                    name: t('navigation.dashboard'),
                    href: '/dashboard',
                    icon: LayoutDashboard,
                    active: currentPath === '/dashboard',
                },
                {
                    name: t('navigation.warehouse_dashboard'),
                    href: '/warehouse',
                    icon: Warehouse,
                    active: currentPath === '/warehouse',
                },
                {
                    name: t('navigation.profitability'),
                    href: '/profitability',
                    icon: TrendingUp,
                    active: currentPath === '/profitability',
                },
            ],
        },
        {
            label: t('navigation.section_documents'),
            items: [
                {
                    name: t('navigation.invoices'),
                    href: '/invoices',
                    icon: FileText,
                    active: currentPath.startsWith('/invoices'),
                },
                {
                    name: t('navigation.proforma_invoices'),
                    href: '/proforma-invoices',
                    icon: FileCheck,
                    active: currentPath.startsWith('/proforma-invoices'),
                },
                {
                    name: t('navigation.offers'),
                    href: '/offers',
                    icon: ClipboardList,
                    active: currentPath.startsWith('/offers'),
                },
            ],
        },
        {
            label: t('navigation.section_manage'),
            items: [
                {
                    name: t('navigation.clients'),
                    href: '/clients',
                    icon: Users,
                    active: currentPath.startsWith('/clients'),
                },
                {
                    name: t('navigation.articles'),
                    href: '/articles',
                    icon: Package,
                    active: currentPath.startsWith('/articles'),
                },
                {
                    name: t('navigation.inventory'),
                    href: '/inventory',
                    icon: Warehouse,
                    active: currentPath.startsWith('/inventory') || currentPath.startsWith('/bundles'),
                },
                {
                    name: t('navigation.goods_receipts'),
                    href: '/goods-receipts',
                    icon: ClipboardCheck,
                    active: currentPath.startsWith('/goods-receipts'),
                },
                {
                    name: t('navigation.goods_issues'),
                    href: '/goods-issues',
                    icon: PackageMinus,
                    active: currentPath.startsWith('/goods-issues'),
                },
                {
                    name: t('navigation.expenses'),
                    href: '/expenses',
                    icon: Receipt,
                    active: currentPath.startsWith('/expenses'),
                },
                {
                    name: t('navigation.bank_transactions'),
                    href: '/bank-transactions',
                    icon: Landmark,
                    active: currentPath.startsWith('/bank-transactions'),
                },
                {
                    name: t('navigation.section_settings'),
                    href: '/settings/profile',
                    icon: Settings,
                    active: currentPath.startsWith('/settings') || currentPath === '/billing',
                },
            ],
        },
    ];

    const isAdmin = auth.isAdmin;
    const subscriptionActive = auth.subscription?.isActive ?? false;

    const handleLogout = () => {
        router.post('/logout');
    };

    const switchLanguage = (lang: string) => {
        window.location.href = `/language/${lang}`;
    };

    const userInitials = auth.user?.name
        ?.split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2) || 'U';

    return (
        <>
            <Toaster />
            <CurrencyCalculator open={calcOpen} onOpenChange={setCalcOpen} />
            <div className="min-h-screen">
                {/* Mobile backdrop */}
                {sidebarOpen && (
                    <div
                        className="fixed inset-0 bg-black/50 z-40 lg:hidden transition-opacity"
                        onClick={() => setSidebarOpen(false)}
                    />
                )}

                {/* Sidebar */}
                <aside
                    className={cn(
                        'fixed inset-y-0 left-0 z-50 w-[280px] bg-white border-r border-gray-200 flex flex-col transition-transform duration-200 ease-out lg:translate-x-0',
                        sidebarOpen ? 'translate-x-0' : '-translate-x-full'
                    )}
                >
                    {/* Logo */}
                    <div className="h-16 flex items-center px-6 border-b border-gray-100">
                        <Link href="/dashboard">
                            <FynvoLogo size={32} className="text-blue-600" textClassName="text-gray-900 text-lg" />
                        </Link>
                        <button
                            onClick={() => setSidebarOpen(false)}
                            className="ml-auto p-2 -mr-2 text-gray-400 hover:text-gray-600 lg:hidden"
                        >
                            <X className="w-5 h-5" />
                        </button>
                    </div>

                    {/* Navigation */}
                    <nav className="flex-1 px-4 py-3 overflow-y-auto">
                        {navSections.map((section, sectionIndex) => (
                            <div key={sectionIndex} className={sectionIndex > 0 ? 'mt-4 pt-4 border-t border-gray-100' : ''}>
                                {section.label && (
                                    <p className="px-3 mb-1 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                                        {section.label}
                                    </p>
                                )}
                                <div className="space-y-0.5">
                                    {section.items.map((item) => (
                                        <Link
                                            key={item.href}
                                            href={item.href}
                                            className={cn(
                                                'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                                item.active
                                                    ? 'bg-blue-50 text-blue-600'
                                                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                            )}
                                            onClick={() => setSidebarOpen(false)}
                                        >
                                            <item.icon className="w-5 h-5" />
                                            {item.name}
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        ))}

                        {/* Tools - hidden until ready
                        <div className="mt-4 pt-4 border-t border-gray-100">
                            <button
                                onClick={() => setCalcOpen(true)}
                                className="flex w-full items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors text-gray-600 hover:bg-gray-50 hover:text-gray-900"
                            >
                                <Calculator className="w-5 h-5" />
                                {t('navigation.currency_calculator')}
                            </button>
                        </div>
                        */}

                        {isAdmin && (
                            <div className="mt-4 pt-4 border-t border-gray-100">
                                <Link
                                    href="/admin"
                                    className="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors text-indigo-600 hover:bg-indigo-50"
                                    onClick={() => setSidebarOpen(false)}
                                >
                                    <Shield className="w-5 h-5" />
                                    {t('navigation.admin_panel')}
                                </Link>
                            </div>
                        )}
                    </nav>

                    {/* Bottom section */}
                    <div className="p-4 border-t border-gray-100">
                        {/* Language switcher */}
                        <div className="flex items-center justify-between mb-4 px-1">
                            <span className="text-xs text-gray-400 uppercase tracking-wider">
                                {t('navigation.language')}
                            </span>
                            <div className="flex gap-1">
                                <button
                                    onClick={() => switchLanguage('mk')}
                                    className={cn(
                                        'px-2.5 py-1 text-xs font-medium rounded transition-colors',
                                        locale === 'mk'
                                            ? 'bg-gray-900 text-white'
                                            : 'text-gray-500 hover:text-gray-700'
                                    )}
                                >
                                    MK
                                </button>
                                <button
                                    onClick={() => switchLanguage('en')}
                                    className={cn(
                                        'px-2.5 py-1 text-xs font-medium rounded transition-colors',
                                        locale === 'en'
                                            ? 'bg-gray-900 text-white'
                                            : 'text-gray-500 hover:text-gray-700'
                                    )}
                                >
                                    EN
                                </button>
                            </div>
                        </div>

                        {/* User */}
                        <div className="flex items-center gap-3 p-2 -mx-2 rounded-lg hover:bg-gray-50 transition-colors">
                            <Avatar className="w-9 h-9">
                                <AvatarFallback className="bg-gray-200 text-gray-600 text-sm font-medium">
                                    {userInitials}
                                </AvatarFallback>
                            </Avatar>
                            <div className="flex-1 min-w-0">
                                <p className="text-sm font-medium text-gray-900 truncate">
                                    {auth.user?.name}
                                </p>
                                <p className="text-xs text-gray-500 truncate">
                                    {auth.user?.email}
                                </p>
                                {!isAdmin && auth.subscription && (
                                    <p className={cn(
                                        'text-xs truncate mt-0.5',
                                        subscriptionActive ? 'text-green-600' : 'text-red-500'
                                    )}>
                                        {subscriptionActive
                                            ? `${t('subscription.expires_at')}: ${formatDate(auth.subscription.expiresAt!)}`
                                            : t('subscription.status_expired')
                                        }
                                    </p>
                                )}
                            </div>
                            <button
                                onClick={handleLogout}
                                className="p-1.5 text-gray-400 hover:text-gray-600 rounded transition-colors"
                                title={t('navigation.logout')}
                            >
                                <LogOut className="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </aside>

                {/* Main content */}
                <div className="lg:ml-[280px] min-h-screen">
                    {/* Mobile header */}
                    <header className="h-14 bg-white border-b border-gray-200 flex items-center px-4 lg:hidden">
                        <button
                            onClick={() => setSidebarOpen(true)}
                            className="p-2 -ml-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg"
                        >
                            <Menu className="w-5 h-5" />
                        </button>
                        <span className="ml-3 font-semibold text-gray-900">
                            <span className="font-bold tracking-tight">Fyn</span>
                            <span className="font-light tracking-tight">vo</span>
                        </span>
                    </header>

                    {/* Impersonation banner */}
                    {impersonating && (
                        <div className="bg-indigo-600 px-6 py-2.5">
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-white">
                                    <Shield className="w-4 h-4 inline mr-2" />
                                    {t('admin.impersonating_banner', { name: auth.user?.name ?? '' })}
                                </p>
                                <button
                                    onClick={() => router.post('/admin/stop-impersonating')}
                                    className="inline-flex items-center px-3 py-1 text-xs font-medium rounded-md bg-white text-indigo-600 hover:bg-indigo-50 transition-colors"
                                >
                                    {t('admin.stop_impersonating')}
                                </button>
                            </div>
                        </div>
                    )}

                    {/* Expired subscription banner */}
                    {!subscriptionActive && !isAdmin && !impersonating && (
                        <div className="bg-yellow-50 border-b border-yellow-200 px-6 py-3">
                            <div className="flex items-center gap-3">
                                <AlertTriangle className="w-5 h-5 text-yellow-600 shrink-0" />
                                <p className="text-sm text-yellow-800 flex-1">
                                    {t('subscription.expired_banner')}
                                </p>
                                <Link
                                    href="/billing"
                                    className="shrink-0 inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-yellow-600 text-white hover:bg-yellow-700 transition-colors"
                                >
                                    {t('navigation.billing')}
                                </Link>
                            </div>
                        </div>
                    )}

                    {/* Content */}
                    <main className="p-6">{children}</main>
                </div>
            </div>
        </>
    );
}

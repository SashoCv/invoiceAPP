import { useState, useEffect, PropsWithChildren } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { useTranslation } from '@/hooks/use-translation';
import { useToast } from '@/hooks/use-toast';
import { Toaster } from '@/Components/ui/toaster';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import {
    LayoutDashboard,
    Users,
    CalendarDays,
    Mail,
    ArrowLeft,
    Shield,
    LogOut,
    Menu,
    X,
} from 'lucide-react';
import FynvoLogo from '@/Components/FynvoLogo';
import type { PageProps } from '@/types';

interface NavItem {
    name: string;
    href: string;
    icon: React.ElementType;
    active: boolean;
}

export default function AdminLayout({ children }: PropsWithChildren) {
    const { t, locale } = useTranslation();
    const { auth, flash } = usePage<PageProps>().props;
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const { toast } = useToast();
    const currentPath = usePage().url;

    useEffect(() => {
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

    const navigation: NavItem[] = [
        {
            name: t('admin.dashboard'),
            href: '/admin',
            icon: LayoutDashboard,
            active: currentPath === '/admin',
        },
        {
            name: t('admin.users'),
            href: '/admin/users',
            icon: Users,
            active: currentPath === '/admin/users' || currentPath.match(/^\/admin\/users\/\d/),
        },
        {
            name: t('admin.expiry_calendar'),
            href: '/admin/users/calendar',
            icon: CalendarDays,
            active: currentPath.startsWith('/admin/users/calendar'),
        },
        {
            name: t('admin.notifications'),
            href: '/admin/notifications',
            icon: Mail,
            active: currentPath.startsWith('/admin/notifications'),
        },
    ];

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
        .slice(0, 2) || 'A';

    return (
        <>
            <Toaster />
            <div className="min-h-screen">
                {sidebarOpen && (
                    <div
                        className="fixed inset-0 bg-black/50 z-40 lg:hidden transition-opacity"
                        onClick={() => setSidebarOpen(false)}
                    />
                )}

                <aside
                    className={cn(
                        'fixed inset-y-0 left-0 z-50 w-[280px] bg-white border-r border-gray-200 flex flex-col transition-transform duration-200 ease-out lg:translate-x-0',
                        sidebarOpen ? 'translate-x-0' : '-translate-x-full'
                    )}
                >
                    {/* Logo */}
                    <div className="h-16 flex items-center px-6 border-b border-gray-100">
                        <Link href="/admin">
                            <FynvoLogo size={32} className="text-indigo-600" showText={false} />
                        </Link>
                        <span className="text-gray-900 font-semibold">{t('admin.admin_panel')}</span>
                        <button
                            onClick={() => setSidebarOpen(false)}
                            className="ml-auto p-2 -mr-2 text-gray-400 hover:text-gray-600 lg:hidden"
                        >
                            <X className="w-5 h-5" />
                        </button>
                    </div>

                    {/* Back to App */}
                    <div className="px-4 pt-4">
                        <Link
                            href="/dashboard"
                            className="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-indigo-600 hover:bg-indigo-50 transition-colors"
                        >
                            <ArrowLeft className="w-4 h-4" />
                            {t('admin.back_to_app')}
                        </Link>
                    </div>

                    {/* Navigation */}
                    <nav className="flex-1 p-4 space-y-1 overflow-y-auto">
                        {navigation.map((item) => (
                            <Link
                                key={item.name}
                                href={item.href}
                                className={cn(
                                    'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                    item.active
                                        ? 'bg-indigo-50 text-indigo-600'
                                        : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                )}
                                onClick={() => setSidebarOpen(false)}
                            >
                                <item.icon className="w-5 h-5" />
                                {item.name}
                            </Link>
                        ))}
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
                                <AvatarFallback className="bg-indigo-100 text-indigo-600 text-sm font-medium">
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
                        <span className="ml-3 font-semibold text-gray-900">{t('admin.admin_panel')}</span>
                    </header>

                    {/* Content */}
                    <main className="p-6">{children}</main>
                </div>
            </div>
        </>
    );
}

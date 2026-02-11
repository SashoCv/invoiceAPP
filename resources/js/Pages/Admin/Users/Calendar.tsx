import { useState } from 'react';
import AdminLayout from '@/Components/AdminLayout';
import { useTranslation } from '@/hooks/use-translation';
import { Card, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, ArrowLeft } from 'lucide-react';

interface ExpiringUser {
    id: number;
    name: string;
    email: string;
    date: string;
    type: 'subscription' | 'trial';
}

interface Props {
    expirationsByDate: Record<string, ExpiringUser[]>;
    month: number;
    year: number;
    totalExpiring: number;
}

export default function Calendar({ expirationsByDate, month, year, totalExpiring }: Props) {
    const { t, locale } = useTranslation();
    const [selectedDay, setSelectedDay] = useState<string | null>(null);

    const today = new Date();
    const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

    const firstDay = new Date(year, month - 1, 1);
    const daysInMonth = new Date(year, month, 0).getDate();

    // Monday = 0, Sunday = 6 (ISO week)
    const startDayOfWeek = (firstDay.getDay() + 6) % 7;

    const monthName = new Intl.DateTimeFormat(locale === 'mk' ? 'mk-MK' : 'en-US', { month: 'long' }).format(firstDay);
    const capitalizedMonth = monthName.charAt(0).toUpperCase() + monthName.slice(1);

    const weekDays = Array.from({ length: 7 }, (_, i) => {
        const date = new Date(2024, 0, i + 1); // Jan 1 2024 is Monday
        return new Intl.DateTimeFormat(locale === 'mk' ? 'mk-MK' : 'en-US', { weekday: 'short' }).format(date);
    });

    const navigateMonth = (direction: -1 | 1) => {
        let newMonth = month + direction;
        let newYear = year;
        if (newMonth < 1) { newMonth = 12; newYear--; }
        if (newMonth > 12) { newMonth = 1; newYear++; }
        router.get('/admin/users/calendar', { month: newMonth, year: newYear }, {
            preserveState: true,
            replace: true,
        });
    };

    const goToToday = () => {
        const now = new Date();
        router.get('/admin/users/calendar', { month: now.getMonth() + 1, year: now.getFullYear() }, {
            preserveState: true,
            replace: true,
        });
    };

    const dateKey = (day: number) =>
        `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

    const selectedUsers = selectedDay ? (expirationsByDate[selectedDay] || []) : [];

    const MAX_VISIBLE = 3;

    return (
        <AdminLayout>
            <div className="flex items-center justify-between mb-6">
                <div className="flex items-center gap-3">
                    <Link href="/admin/users">
                        <Button variant="outline" size="sm" className="gap-2">
                            <ArrowLeft className="w-4 h-4" />
                            {t('admin.users')}
                        </Button>
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900">{t('admin.expiry_calendar')}</h1>
                </div>
            </div>

            <Card>
                <CardContent className="pt-6">
                    {/* Month navigation */}
                    <div className="flex items-center justify-between mb-6">
                        <div className="flex items-center gap-2">
                            <Button variant="outline" size="sm" onClick={() => navigateMonth(-1)}>
                                <ChevronLeft className="w-4 h-4" />
                            </Button>
                            <h2 className="text-lg font-semibold text-gray-900 min-w-[200px] text-center">
                                {capitalizedMonth} {year}
                            </h2>
                            <Button variant="outline" size="sm" onClick={() => navigateMonth(1)}>
                                <ChevronRight className="w-4 h-4" />
                            </Button>
                            <Button variant="outline" size="sm" onClick={goToToday} className="ml-2">
                                {t('admin.today')}
                            </Button>
                        </div>
                        {totalExpiring > 0 && (
                            <Badge variant="destructive">
                                {t('admin.total_expiring', { count: String(totalExpiring) })}
                            </Badge>
                        )}
                    </div>

                    {/* Calendar grid */}
                    <div className="grid grid-cols-7 border-t border-l border-gray-200">
                        {/* Weekday headers */}
                        {weekDays.map((day, i) => (
                            <div key={i} className="border-b border-r border-gray-200 bg-gray-50 px-2 py-2 text-center text-xs font-medium text-gray-500 uppercase">
                                {day}
                            </div>
                        ))}

                        {/* Empty cells for alignment */}
                        {Array.from({ length: startDayOfWeek }, (_, i) => (
                            <div key={`empty-${i}`} className="border-b border-r border-gray-200 bg-gray-50/50 min-h-[100px]" />
                        ))}

                        {/* Day cells */}
                        {Array.from({ length: daysInMonth }, (_, i) => {
                            const day = i + 1;
                            const key = dateKey(day);
                            const users = expirationsByDate[key] || [];
                            const isToday = key === todayStr;
                            const hasUsers = users.length > 0;

                            return (
                                <div
                                    key={day}
                                    className={`border-b border-r border-gray-200 min-h-[100px] p-1.5 ${
                                        isToday ? 'bg-indigo-50/50' : ''
                                    } ${hasUsers ? 'cursor-pointer hover:bg-gray-50' : ''}`}
                                    onClick={() => hasUsers && setSelectedDay(key)}
                                >
                                    <div className={`text-sm font-medium mb-1 w-7 h-7 flex items-center justify-center rounded-full ${
                                        isToday ? 'bg-indigo-600 text-white' : 'text-gray-700'
                                    }`}>
                                        {day}
                                    </div>
                                    <div className="space-y-0.5">
                                        {users.slice(0, MAX_VISIBLE).map((user) => (
                                            <Link
                                                key={user.id}
                                                href={`/admin/users/${user.id}`}
                                                onClick={(e) => e.stopPropagation()}
                                                className={`block text-xs px-1.5 py-0.5 rounded truncate ${
                                                    user.type === 'subscription'
                                                        ? 'bg-red-100 text-red-700 hover:bg-red-200'
                                                        : 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200'
                                                }`}
                                            >
                                                {user.name}
                                            </Link>
                                        ))}
                                        {users.length > MAX_VISIBLE && (
                                            <div className="text-xs text-gray-500 px-1.5">
                                                +{users.length - MAX_VISIBLE} {t('admin.more')}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {totalExpiring === 0 && (
                        <p className="text-center text-sm text-gray-500 py-8">
                            {t('admin.no_expirations')}
                        </p>
                    )}
                </CardContent>
            </Card>

            {/* Day detail dialog */}
            <Dialog open={!!selectedDay} onOpenChange={() => setSelectedDay(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {selectedDay && new Intl.DateTimeFormat(locale === 'mk' ? 'mk-MK' : 'en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                            }).format(new Date(selectedDay + 'T00:00:00'))}
                        </DialogTitle>
                    </DialogHeader>
                    <div className="space-y-3 py-2">
                        {selectedUsers.map((user) => (
                            <div key={user.id} className="flex items-center justify-between gap-3 p-3 rounded-lg border border-gray-200">
                                <div className="min-w-0 flex-1">
                                    <Link
                                        href={`/admin/users/${user.id}`}
                                        className="text-sm font-medium text-gray-900 hover:text-indigo-600"
                                    >
                                        {user.name}
                                    </Link>
                                    <a
                                        href={`mailto:${user.email}`}
                                        className="block text-xs text-gray-500 hover:text-indigo-600 truncate"
                                    >
                                        {user.email}
                                    </a>
                                </div>
                                <Badge variant={user.type === 'subscription' ? 'destructive' : 'warning'}>
                                    {user.type === 'subscription'
                                        ? t('admin.subscription_expiry')
                                        : t('admin.trial_expiry')}
                                </Badge>
                            </div>
                        ))}
                    </div>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}

import AdminLayout from '@/Components/AdminLayout';
import { useTranslation } from '@/hooks/use-translation';
import { Card, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import Pagination from '@/Components/Pagination';
import { formatDate } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { Plus, Mail } from 'lucide-react';
import type { PaginatedData } from '@/types';

interface Notification {
    id: number;
    subject: string;
    audience: string;
    audience_label: string;
    sent_count: number;
    status: string;
    sent_by_name: string | null;
    sent_at: string | null;
    created_at: string;
}

interface Props {
    notifications: PaginatedData<Notification>;
}

const statusVariant = (status: string) => {
    switch (status) {
        case 'sent': return 'success';
        case 'sending': return 'info';
        case 'pending': return 'gray';
        case 'failed': return 'destructive';
        default: return 'gray';
    }
};

export default function NotificationsIndex({ notifications }: Props) {
    const { t } = useTranslation();

    return (
        <AdminLayout>
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold text-gray-900">{t('admin.notifications')}</h1>
                <Button asChild>
                    <Link href="/admin/notifications/create">
                        <Plus className="w-4 h-4 mr-2" />
                        {t('admin.new_notification')}
                    </Link>
                </Button>
            </div>

            <Card>
                <CardContent className="pt-6">
                    {notifications.data.length === 0 ? (
                        <div className="text-center py-12">
                            <Mail className="w-12 h-12 text-gray-300 mx-auto mb-4" />
                            <p className="text-gray-500">{t('admin.no_notifications')}</p>
                        </div>
                    ) : (
                        <>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-gray-200">
                                            <th className="text-left py-3 px-2 font-medium text-gray-500">{t('admin.subject')}</th>
                                            <th className="text-left py-3 px-2 font-medium text-gray-500">{t('admin.audience')}</th>
                                            <th className="text-left py-3 px-2 font-medium text-gray-500">{t('admin.sent_count')}</th>
                                            <th className="text-left py-3 px-2 font-medium text-gray-500">{t('admin.status')}</th>
                                            <th className="text-left py-3 px-2 font-medium text-gray-500">{t('admin.sent_by')}</th>
                                            <th className="text-left py-3 px-2 font-medium text-gray-500">{t('admin.sent_at')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {notifications.data.map((notification) => (
                                            <tr key={notification.id} className="border-b border-gray-100 last:border-0">
                                                <td className="py-3 px-2 font-medium text-gray-900">
                                                    {notification.subject}
                                                </td>
                                                <td className="py-3 px-2 text-gray-500">
                                                    {notification.audience_label}
                                                </td>
                                                <td className="py-3 px-2 text-gray-500">
                                                    {notification.sent_count}
                                                </td>
                                                <td className="py-3 px-2">
                                                    <Badge variant={statusVariant(notification.status) as 'success' | 'info' | 'destructive' | 'gray'}>
                                                        {t(`admin.status_${notification.status}`)}
                                                    </Badge>
                                                </td>
                                                <td className="py-3 px-2 text-gray-500">
                                                    {notification.sent_by_name}
                                                </td>
                                                <td className="py-3 px-2 text-gray-500">
                                                    {notification.sent_at
                                                        ? formatDate(notification.sent_at)
                                                        : formatDate(notification.created_at)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <Pagination
                                links={notifications.links}
                                from={notifications.from}
                                to={notifications.to}
                                total={notifications.total}
                                className="mt-4"
                            />
                        </>
                    )}
                </CardContent>
            </Card>
        </AdminLayout>
    );
}

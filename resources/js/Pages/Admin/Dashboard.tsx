import AdminLayout from '@/Components/AdminLayout';
import { useTranslation } from '@/hooks/use-translation';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { formatDate } from '@/lib/utils';
import { Users, CheckCircle, XCircle, UserPlus } from 'lucide-react';
import { Link } from '@inertiajs/react';

interface UserRow {
    id: number;
    name: string;
    email: string;
    role: string;
    subscription_status: string;
    days_remaining: number | null;
    created_at: string;
}

interface Props {
    stats: {
        totalUsers: number;
        activeSubscriptions: number;
        expiredUsers: number;
        newThisMonth: number;
        trialUsers: number;
    };
    recentUsers: UserRow[];
}

const statusVariant = (status: string) => {
    switch (status) {
        case 'active': return 'success';
        case 'trial': return 'info';
        case 'expired': return 'destructive';
        case 'admin': return 'secondary';
        default: return 'gray';
    }
};

export default function AdminDashboard({ stats, recentUsers }: Props) {
    const { t } = useTranslation();

    const statCards = [
        { label: t('admin.total_users'), value: stats.totalUsers, icon: Users, color: 'text-blue-600 bg-blue-50' },
        { label: t('admin.active_subscriptions'), value: stats.activeSubscriptions, icon: CheckCircle, color: 'text-green-600 bg-green-50' },
        { label: t('admin.expired_users'), value: stats.expiredUsers, icon: XCircle, color: 'text-red-600 bg-red-50' },
        { label: t('admin.new_this_month'), value: stats.newThisMonth, icon: UserPlus, color: 'text-indigo-600 bg-indigo-50' },
    ];

    return (
        <AdminLayout>
            <h1 className="text-2xl font-bold text-gray-900 mb-6">{t('admin.dashboard')}</h1>

            {/* Stat cards */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                {statCards.map((stat) => (
                    <Card key={stat.label}>
                        <CardContent className="p-6">
                            <div className="flex items-center gap-4">
                                <div className={`p-3 rounded-lg ${stat.color}`}>
                                    <stat.icon className="w-5 h-5" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">{stat.label}</p>
                                    <p className="text-2xl font-bold text-gray-900">{stat.value}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>

            {/* Recent users */}
            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <CardTitle className="text-lg">{t('admin.recent_users')}</CardTitle>
                        <Link
                            href="/admin/users"
                            className="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                        >
                            {t('admin.users')} &rarr;
                        </Link>
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-gray-200">
                                    <th className="text-left py-3 px-2 font-medium text-gray-500">{t('admin.name')}</th>
                                    <th className="text-left py-3 px-2 font-medium text-gray-500">{t('admin.email')}</th>
                                    <th className="text-left py-3 px-2 font-medium text-gray-500">{t('admin.status')}</th>
                                    <th className="text-left py-3 px-2 font-medium text-gray-500">{t('admin.member_since')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {recentUsers.map((user) => (
                                    <tr key={user.id} className="border-b border-gray-100 last:border-0">
                                        <td className="py-3 px-2">
                                            <Link href={`/admin/users/${user.id}`} className="text-gray-900 hover:text-indigo-600 font-medium">
                                                {user.name}
                                            </Link>
                                        </td>
                                        <td className="py-3 px-2 text-gray-500">{user.email}</td>
                                        <td className="py-3 px-2">
                                            <Badge variant={statusVariant(user.subscription_status) as 'success' | 'info' | 'destructive' | 'secondary' | 'gray'}>
                                                {t(`subscription.status_${user.subscription_status}`)}
                                            </Badge>
                                        </td>
                                        <td className="py-3 px-2 text-gray-500">{formatDate(user.created_at)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </AdminLayout>
    );
}

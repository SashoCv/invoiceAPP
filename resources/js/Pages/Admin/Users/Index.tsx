import { useState } from 'react';
import AdminLayout from '@/Components/AdminLayout';
import { useTranslation } from '@/hooks/use-translation';
import { Card, CardContent, CardHeader } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import Pagination from '@/Components/Pagination';
import ActionDropdown from '@/Components/ActionDropdown';
import { formatDate } from '@/lib/utils';
import { Link, router } from '@inertiajs/react';
import { Search, Eye, CalendarPlus, Ban, Shield } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/Components/ui/dialog';
import type { PaginatedData } from '@/types';

interface AdminUser {
    id: number;
    name: string;
    email: string;
    role: string;
    subscription_status: string;
    subscription_expires_at: string | null;
    trial_ends_at: string | null;
    days_remaining: number | null;
    created_at: string;
}

interface Props {
    users: PaginatedData<AdminUser>;
    filters: {
        search?: string;
        status?: string;
    };
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

export default function UsersIndex({ users, filters }: Props) {
    const { t } = useTranslation();
    const [search, setSearch] = useState(filters.search ?? '');
    const [extendDialogUser, setExtendDialogUser] = useState<AdminUser | null>(null);
    const [extendDays, setExtendDays] = useState('30');
    const [revokeDialogUser, setRevokeDialogUser] = useState<AdminUser | null>(null);

    const handleSearch = (value: string) => {
        setSearch(value);
        router.get('/admin/users', { search: value, status: filters.status }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleStatusFilter = (status: string) => {
        router.get('/admin/users', { search: filters.search, status }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleExtend = () => {
        if (!extendDialogUser) return;
        router.post(`/admin/users/${extendDialogUser.id}/extend`, {
            days: parseInt(extendDays),
        }, {
            onSuccess: () => setExtendDialogUser(null),
        });
    };

    const handleRevoke = () => {
        if (!revokeDialogUser) return;
        router.post(`/admin/users/${revokeDialogUser.id}/revoke`, {}, {
            onSuccess: () => setRevokeDialogUser(null),
        });
    };

    const handleToggleAdmin = (user: AdminUser) => {
        router.post(`/admin/users/${user.id}/toggle-admin`);
    };

    return (
        <AdminLayout>
            <h1 className="text-2xl font-bold text-gray-900 mb-6">{t('admin.users')}</h1>

            <Card>
                <CardHeader>
                    <div className="flex flex-col sm:flex-row gap-3">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <Input
                                value={search}
                                onChange={(e) => handleSearch(e.target.value)}
                                placeholder={t('admin.search_users')}
                                className="pl-9"
                            />
                        </div>
                        <Select
                            value={filters.status || 'all'}
                            onValueChange={handleStatusFilter}
                        >
                            <SelectTrigger className="w-[180px]">
                                <SelectValue placeholder={t('admin.filter_status')} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">{t('admin.all')}</SelectItem>
                                <SelectItem value="admin">{t('admin.admins')}</SelectItem>
                                <SelectItem value="active">{t('admin.active')}</SelectItem>
                                <SelectItem value="trial">{t('admin.trial')}</SelectItem>
                                <SelectItem value="expired">{t('admin.expired')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-gray-200">
                                    <th className="text-left py-3 px-2 font-medium text-gray-500">{t('admin.name')}</th>
                                    <th className="text-left py-3 px-2 font-medium text-gray-500">{t('admin.email')}</th>
                                    <th className="text-left py-3 px-2 font-medium text-gray-500">{t('admin.role')}</th>
                                    <th className="text-left py-3 px-2 font-medium text-gray-500">{t('admin.status')}</th>
                                    <th className="text-left py-3 px-2 font-medium text-gray-500">{t('admin.expires_at')}</th>
                                    <th className="text-right py-3 px-2 font-medium text-gray-500">{t('admin.actions')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {users.data.map((user) => (
                                    <tr key={user.id} className="border-b border-gray-100 last:border-0">
                                        <td className="py-3 px-2">
                                            <Link href={`/admin/users/${user.id}`} className="text-gray-900 hover:text-indigo-600 font-medium">
                                                {user.name}
                                            </Link>
                                        </td>
                                        <td className="py-3 px-2 text-gray-500">{user.email}</td>
                                        <td className="py-3 px-2">
                                            <Badge variant={user.role === 'admin' ? 'secondary' : 'gray'}>
                                                {user.role}
                                            </Badge>
                                        </td>
                                        <td className="py-3 px-2">
                                            <Badge variant={statusVariant(user.subscription_status) as 'success' | 'info' | 'destructive' | 'secondary' | 'gray'}>
                                                {t(`subscription.status_${user.subscription_status}`)}
                                            </Badge>
                                        </td>
                                        <td className="py-3 px-2 text-gray-500">
                                            {user.subscription_expires_at
                                                ? formatDate(user.subscription_expires_at)
                                                : user.trial_ends_at
                                                    ? formatDate(user.trial_ends_at)
                                                    : t('admin.never')}
                                        </td>
                                        <td className="py-3 px-2 text-right">
                                            <ActionDropdown
                                                actions={[
                                                    {
                                                        label: t('admin.view'),
                                                        icon: Eye,
                                                        href: `/admin/users/${user.id}`,
                                                    },
                                                    {
                                                        label: t('admin.extend'),
                                                        icon: CalendarPlus,
                                                        onClick: () => {
                                                            setExtendDays('30');
                                                            setExtendDialogUser(user);
                                                        },
                                                        hidden: user.role === 'admin',
                                                    },
                                                    {
                                                        label: t('admin.revoke'),
                                                        icon: Ban,
                                                        onClick: () => setRevokeDialogUser(user),
                                                        variant: 'destructive',
                                                        hidden: user.role === 'admin',
                                                    },
                                                    {
                                                        label: user.role === 'admin' ? t('admin.remove_admin') : t('admin.make_admin'),
                                                        icon: Shield,
                                                        onClick: () => handleToggleAdmin(user),
                                                    },
                                                ]}
                                            />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <Pagination
                        links={users.links}
                        from={users.from}
                        to={users.to}
                        total={users.total}
                        className="mt-4"
                    />
                </CardContent>
            </Card>

            {/* Extend Dialog */}
            <Dialog open={!!extendDialogUser} onOpenChange={() => setExtendDialogUser(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('admin.extend_subscription')}</DialogTitle>
                    </DialogHeader>
                    <div className="py-4">
                        <p className="text-sm text-gray-500 mb-4">
                            {extendDialogUser?.name} ({extendDialogUser?.email})
                        </p>
                        <div className="flex items-center gap-3">
                            <Input
                                type="number"
                                value={extendDays}
                                onChange={(e) => setExtendDays(e.target.value)}
                                min={1}
                                max={365}
                                className="w-32"
                            />
                            <span className="text-sm text-gray-500">{t('admin.days')}</span>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setExtendDialogUser(null)}>
                            {t('general.cancel')}
                        </Button>
                        <Button onClick={handleExtend}>
                            {t('admin.extend')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Revoke Dialog */}
            <Dialog open={!!revokeDialogUser} onOpenChange={() => setRevokeDialogUser(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('admin.revoke')}</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-gray-500 py-4">
                        {t('admin.revoke_confirm')}
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setRevokeDialogUser(null)}>
                            {t('general.cancel')}
                        </Button>
                        <Button variant="destructive" onClick={handleRevoke}>
                            {t('admin.revoke')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}

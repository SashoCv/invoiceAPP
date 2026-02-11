import { useState } from 'react';
import AdminLayout from '@/Components/AdminLayout';
import { useTranslation } from '@/hooks/use-translation';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { formatDate } from '@/lib/utils';
import { router, Link } from '@inertiajs/react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/Components/ui/dialog';
import {
    User,
    Mail,
    Calendar,
    Clock,
    FileText,
    Users,
    Building2,
    Shield,
    ArrowLeft,
    CalendarPlus,
    Ban,
} from 'lucide-react';

interface UserData {
    id: number;
    name: string;
    email: string;
    role: string;
    subscription_status: string;
    subscription_expires_at: string | null;
    trial_ends_at: string | null;
    days_remaining: number | null;
    created_at: string;
    invoices_count: number;
    clients_count: number;
    agency: string | null;
}

interface Props {
    userData: UserData;
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

export default function UserShow({ userData }: Props) {
    const { t } = useTranslation();
    const [extendOpen, setExtendOpen] = useState(false);
    const [extendDays, setExtendDays] = useState('30');
    const [revokeOpen, setRevokeOpen] = useState(false);

    const handleExtend = () => {
        router.post(`/admin/users/${userData.id}/extend`, {
            days: parseInt(extendDays),
        }, {
            onSuccess: () => setExtendOpen(false),
        });
    };

    const handleRevoke = () => {
        router.post(`/admin/users/${userData.id}/revoke`, {}, {
            onSuccess: () => setRevokeOpen(false),
        });
    };

    const handleToggleAdmin = () => {
        router.post(`/admin/users/${userData.id}/toggle-admin`);
    };

    return (
        <AdminLayout>
            <div className="mb-6">
                <Link href="/admin/users" className="text-sm text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                    <ArrowLeft className="w-4 h-4" />
                    {t('admin.users')}
                </Link>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* User info */}
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle className="text-lg flex items-center gap-2">
                            <User className="w-5 h-5" />
                            {t('admin.user_details')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <p className="text-xs text-gray-400 uppercase tracking-wider mb-1">{t('admin.name')}</p>
                                <p className="text-sm font-medium text-gray-900">{userData.name}</p>
                            </div>
                            <div>
                                <p className="text-xs text-gray-400 uppercase tracking-wider mb-1">{t('admin.email')}</p>
                                <p className="text-sm text-gray-600 flex items-center gap-1">
                                    <Mail className="w-3.5 h-3.5" />
                                    {userData.email}
                                </p>
                            </div>
                            <div>
                                <p className="text-xs text-gray-400 uppercase tracking-wider mb-1">{t('admin.role')}</p>
                                <Badge variant={userData.role === 'admin' ? 'secondary' : 'gray'}>
                                    {userData.role}
                                </Badge>
                            </div>
                            <div>
                                <p className="text-xs text-gray-400 uppercase tracking-wider mb-1">{t('admin.member_since')}</p>
                                <p className="text-sm text-gray-600 flex items-center gap-1">
                                    <Calendar className="w-3.5 h-3.5" />
                                    {formatDate(userData.created_at)}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Subscription info */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">{t('admin.subscription_info')}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <p className="text-xs text-gray-400 uppercase tracking-wider mb-1">{t('admin.status')}</p>
                            <Badge variant={statusVariant(userData.subscription_status) as 'success' | 'info' | 'destructive' | 'secondary' | 'gray'}>
                                {t(`subscription.status_${userData.subscription_status}`)}
                            </Badge>
                        </div>

                        {userData.subscription_expires_at && (
                            <div>
                                <p className="text-xs text-gray-400 uppercase tracking-wider mb-1">{t('admin.expires_at')}</p>
                                <p className="text-sm text-gray-600">{formatDate(userData.subscription_expires_at)}</p>
                            </div>
                        )}

                        {userData.days_remaining !== null && userData.days_remaining > 0 && (
                            <div>
                                <p className="text-xs text-gray-400 uppercase tracking-wider mb-1">{t('subscription.days_remaining')}</p>
                                <p className="text-sm font-medium text-gray-900 flex items-center gap-1">
                                    <Clock className="w-3.5 h-3.5" />
                                    {userData.days_remaining}
                                </p>
                            </div>
                        )}

                        {userData.role !== 'admin' && (
                            <div className="pt-4 space-y-2 border-t">
                                <Button
                                    size="sm"
                                    className="w-full"
                                    onClick={() => {
                                        setExtendDays('30');
                                        setExtendOpen(true);
                                    }}
                                >
                                    <CalendarPlus className="w-4 h-4 mr-2" />
                                    {t('admin.extend')}
                                </Button>
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    className="w-full"
                                    onClick={() => setRevokeOpen(true)}
                                >
                                    <Ban className="w-4 h-4 mr-2" />
                                    {t('admin.revoke')}
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    className="w-full"
                                    onClick={handleToggleAdmin}
                                >
                                    <Shield className="w-4 h-4 mr-2" />
                                    {userData.role === 'admin' ? t('admin.remove_admin') : t('admin.make_admin')}
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Stats */}
                <Card className="lg:col-span-3">
                    <CardHeader>
                        <CardTitle className="text-lg">{t('admin.user_statistics')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
                            <div className="flex items-center gap-3">
                                <div className="p-3 rounded-lg bg-blue-50 text-blue-600">
                                    <FileText className="w-5 h-5" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">{t('admin.invoices_count')}</p>
                                    <p className="text-xl font-bold text-gray-900">{userData.invoices_count}</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-3">
                                <div className="p-3 rounded-lg bg-green-50 text-green-600">
                                    <Users className="w-5 h-5" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">{t('admin.clients_count')}</p>
                                    <p className="text-xl font-bold text-gray-900">{userData.clients_count}</p>
                                </div>
                            </div>
                            <div className="flex items-center gap-3">
                                <div className="p-3 rounded-lg bg-indigo-50 text-indigo-600">
                                    <Building2 className="w-5 h-5" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">{t('admin.agency')}</p>
                                    <p className="text-sm font-medium text-gray-900">
                                        {userData.agency ?? t('admin.no_agency')}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Extend Dialog */}
            <Dialog open={extendOpen} onOpenChange={setExtendOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('admin.extend_subscription')}</DialogTitle>
                    </DialogHeader>
                    <div className="py-4">
                        <p className="text-sm text-gray-500 mb-4">
                            {userData.name} ({userData.email})
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
                        <Button variant="outline" onClick={() => setExtendOpen(false)}>
                            {t('general.cancel')}
                        </Button>
                        <Button onClick={handleExtend}>
                            {t('admin.extend')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Revoke Dialog */}
            <Dialog open={revokeOpen} onOpenChange={setRevokeOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('admin.revoke')}</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-gray-500 py-4">
                        {t('admin.revoke_confirm')}
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setRevokeOpen(false)}>
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

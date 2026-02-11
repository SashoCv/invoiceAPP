import { useState } from 'react';
import AdminLayout from '@/Components/AdminLayout';
import { useTranslation } from '@/hooks/use-translation';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/Components/ui/dialog';
import { RichTextEditor } from '@/Components/ui/rich-text-editor';
import { useForm, Link } from '@inertiajs/react';
import { ArrowLeft, Send } from 'lucide-react';

export default function NotificationsCreate() {
    const { t } = useTranslation();
    const [showConfirm, setShowConfirm] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        subject: '',
        audience: 'all',
        body: '',
    });

    const handleSubmit = () => {
        post('/admin/notifications', {
            onSuccess: () => setShowConfirm(false),
        });
    };

    return (
        <AdminLayout>
            <div className="mb-6">
                <Link
                    href="/admin/notifications"
                    className="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-3"
                >
                    <ArrowLeft className="w-4 h-4" />
                    {t('admin.notifications')}
                </Link>
                <h1 className="text-2xl font-bold text-gray-900">{t('admin.compose_notification')}</h1>
            </div>

            <Card>
                <CardContent className="pt-6">
                    <div className="space-y-6 max-w-2xl">
                        <div>
                            <Label htmlFor="subject">{t('admin.subject')}</Label>
                            <Input
                                id="subject"
                                value={data.subject}
                                onChange={(e) => setData('subject', e.target.value)}
                                className="mt-1.5"
                            />
                            {errors.subject && (
                                <p className="text-sm text-red-500 mt-1">{errors.subject}</p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="audience">{t('admin.audience')}</Label>
                            <Select
                                value={data.audience}
                                onValueChange={(value) => setData('audience', value)}
                            >
                                <SelectTrigger className="mt-1.5">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">{t('admin.audience_all')}</SelectItem>
                                    <SelectItem value="active">{t('admin.audience_active')}</SelectItem>
                                    <SelectItem value="expired">{t('admin.audience_expired')}</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.audience && (
                                <p className="text-sm text-red-500 mt-1">{errors.audience}</p>
                            )}
                        </div>

                        <div>
                            <Label>{t('admin.body')}</Label>
                            <RichTextEditor
                                value={data.body}
                                onChange={(value) => setData('body', value)}
                                placeholder={t('admin.body_placeholder')}
                                className="mt-1.5"
                            />
                            {errors.body && (
                                <p className="text-sm text-red-500 mt-1">{errors.body}</p>
                            )}
                        </div>

                        <div className="flex items-center gap-3 pt-2">
                            <Button
                                onClick={() => setShowConfirm(true)}
                                disabled={processing || !data.subject || !data.body}
                            >
                                <Send className="w-4 h-4 mr-2" />
                                {t('admin.send_notification')}
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href="/admin/notifications">
                                    {t('general.cancel')}
                                </Link>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Dialog open={showConfirm} onOpenChange={setShowConfirm}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('admin.confirm_send')}</DialogTitle>
                    </DialogHeader>
                    <p className="text-sm text-gray-500 py-4">
                        {t('admin.confirm_send_message')}
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowConfirm(false)}>
                            {t('general.cancel')}
                        </Button>
                        <Button onClick={handleSubmit} disabled={processing}>
                            <Send className="w-4 h-4 mr-2" />
                            {t('admin.send_notification')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}

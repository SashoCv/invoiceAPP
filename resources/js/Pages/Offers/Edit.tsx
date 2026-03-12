import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { RichTextEditor } from '@/Components/ui/rich-text-editor';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import { ArrowLeft } from 'lucide-react';
import type { Offer, Client } from '@/types';

interface EditOfferProps {
    offer: Offer;
    clients: Client[];
}

export default function EditOffer({ offer, clients }: EditOfferProps) {
    const { t } = useTranslation();

    const { data, setData, put, processing, errors } = useForm({
        client_id: offer.client_id.toString(),
        currency: offer.currency,
        issue_date: offer.issue_date,
        valid_until: offer.valid_until,
        offer_prefix: offer.offer_prefix || '',
        offer_sequence: offer.offer_sequence,
        title: offer.title,
        content: offer.content || '',
        status: offer.status,
        notes: offer.notes || '',
        total: offer.total || 0,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(`/offers/${offer.id}`);
    };

    return (
        <AppLayout>
            <Head title={`${t('offers.edit_title')} - ${offer.offer_number}`} />

            <div className="max-w-4xl mx-auto">
                {/* Header */}
                <div className="mb-6">
                    <Button variant="ghost" size="sm" asChild className="mb-4">
                        <Link href={`/offers/${offer.id}`} className="flex items-center gap-2">
                            <ArrowLeft className="w-4 h-4" />
                            {t('offers.back_to_offer')}
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-bold text-gray-900">{t('offers.edit_title')}</h1>
                    <p className="mt-1 text-sm text-gray-500">{offer.offer_number}</p>
                </div>

                <form onSubmit={submit}>
                    {/* Basic Info */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>{t('offers.basic_info')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="client_id">{t('offers.client')} *</Label>
                                    <Select value={data.client_id} onValueChange={(v) => setData('client_id', v)}>
                                        <SelectTrigger className="mt-1" error={errors.client_id}>
                                            <SelectValue placeholder={t('offers.select_client')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {clients.map((c) => (
                                                <SelectItem key={c.id} value={c.id.toString()}>
                                                    {c.company || c.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Label htmlFor="status">{t('offers.status')} *</Label>
                                    <Select value={data.status} onValueChange={(v: Offer['status']) => setData('status', v)}>
                                        <SelectTrigger className="mt-1">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="draft">{t('offers.status_draft')}</SelectItem>
                                            <SelectItem value="sent">{t('offers.status_sent')}</SelectItem>
                                            <SelectItem value="accepted">{t('offers.status_accepted')}</SelectItem>
                                            <SelectItem value="rejected">{t('offers.status_rejected')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="title">{t('offers.offer_title')} *</Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    className="mt-1"
                                    placeholder={t('offers.title_placeholder')}
                                    error={errors.title}
                                />
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <Label htmlFor="currency">{t('offers.currency')} *</Label>
                                    <Select value={data.currency} onValueChange={(v) => setData('currency', v)}>
                                        <SelectTrigger className="mt-1">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="MKD">MKD - Денари</SelectItem>
                                            <SelectItem value="EUR">EUR - Евро</SelectItem>
                                            <SelectItem value="USD">USD - Долари</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label htmlFor="issue_date">{t('offers.issue_date')} *</Label>
                                    <Input
                                        id="issue_date"
                                        type="date"
                                        value={data.issue_date}
                                        onChange={(e) => setData('issue_date', e.target.value)}
                                        className="mt-1"
                                        error={errors.issue_date}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="valid_until">{t('offers.valid_until')} *</Label>
                                    <Input
                                        id="valid_until"
                                        type="date"
                                        value={data.valid_until}
                                        onChange={(e) => setData('valid_until', e.target.value)}
                                        className="mt-1"
                                        error={errors.valid_until}
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Content */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>{t('offers.content')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <RichTextEditor
                                value={data.content}
                                onChange={(value) => setData('content', value)}
                                placeholder={t('offers.content_placeholder')}
                            />
                        </CardContent>
                    </Card>

                    {/* Total */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>{t('offers.total_value')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="max-w-xs">
                                <Label htmlFor="total">{t('offers.total')} ({data.currency})</Label>
                                <Input
                                    id="total"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.total}
                                    onChange={(e) => setData('total', parseFloat(e.target.value) || 0)}
                                    className="mt-1"
                                    error={errors.total}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Notes */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>{t('offers.notes')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Textarea
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                placeholder={t('offers.notes_placeholder')}
                                rows={4}
                            />
                        </CardContent>
                    </Card>

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-4">
                        <Button variant="outline" asChild>
                            <Link href={`/offers/${offer.id}`}>{t('general.cancel')}</Link>
                        </Button>
                        <Button type="submit" disabled={processing} loading={processing}>
                            {t('offers.update_offer')}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

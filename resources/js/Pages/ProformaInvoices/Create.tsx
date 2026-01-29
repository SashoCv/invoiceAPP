import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/utils';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
import type { Client, Article, ProformaInvoice } from '@/types';

interface ProformaItem {
    article_id: string;
    description: string;
    quantity: number;
    unit_price: number;
    tax_rate: number;
    is_manual: boolean;
}

interface CreateProformaProps {
    clients: Client[];
    articles: Article[];
    currentYear: number;
    nextSequence: number;
    proforma?: ProformaInvoice;
    isDuplicate?: boolean;
}

export default function CreateProforma({ clients, articles, currentYear, nextSequence, proforma, isDuplicate }: CreateProformaProps) {
    const { t } = useTranslation();

    const { data, setData, post, processing, errors } = useForm({
        client_id: proforma?.client_id?.toString() || '',
        currency: proforma?.currency || 'MKD',
        issue_date: new Date().toISOString().split('T')[0],
        valid_until: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
        proforma_prefix: proforma?.proforma_prefix || '',
        proforma_sequence: nextSequence,
        notes: proforma?.notes || '',
        items: proforma?.items?.map((item) => ({
            article_id: item.article_id?.toString() || '',
            description: item.description,
            quantity: item.quantity,
            unit_price: item.unit_price,
            tax_rate: item.tax_rate,
            is_manual: !item.article_id,
        })) || [{ article_id: '', description: '', quantity: 1, unit_price: 0, tax_rate: 18, is_manual: false }] as ProformaItem[],
    });

    const addItem = () => {
        setData('items', [...data.items, { article_id: '', description: '', quantity: 1, unit_price: 0, tax_rate: 18, is_manual: false }]);
    };

    const removeItem = (index: number) => {
        if (data.items.length > 1) {
            setData('items', data.items.filter((_, i) => i !== index));
        }
    };

    const updateItem = (index: number, field: keyof ProformaItem, value: string | number) => {
        const items = [...data.items];
        items[index] = { ...items[index], [field]: value };
        setData('items', items);
    };

    const selectArticle = (index: number, articleId: string) => {
        const article = articles.find((a) => a.id.toString() === articleId);
        if (article) {
            const items = [...data.items];
            items[index] = {
                ...items[index],
                article_id: articleId,
                description: article.name,
                unit_price: article.price,
                tax_rate: article.tax_rate,
                is_manual: false,
            };
            setData('items', items);
        }
    };

    const setItemMode = (index: number, isManual: boolean) => {
        const items = [...data.items];
        items[index] = {
            ...items[index],
            is_manual: isManual,
            article_id: '',
            description: '',
            unit_price: 0,
            tax_rate: 18,
        };
        setData('items', items);
    };

    const calculateItemTotal = (item: ProformaItem) => {
        const subtotal = item.quantity * item.unit_price;
        const tax = subtotal * (item.tax_rate / 100);
        return subtotal + tax;
    };

    const subtotal = data.items.reduce((sum, item) => sum + item.quantity * item.unit_price, 0);
    const taxAmount = data.items.reduce((sum, item) => sum + item.quantity * item.unit_price * (item.tax_rate / 100), 0);
    const total = subtotal + taxAmount;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/proforma-invoices');
    };

    return (
        <AppLayout>
            <Head title={t('proforma.create_title')} />

            <div className="max-w-4xl mx-auto">
                {/* Header */}
                <div className="mb-6">
                    <Button variant="ghost" size="sm" asChild className="mb-4">
                        <Link href="/proforma-invoices" className="flex items-center gap-2">
                            <ArrowLeft className="w-4 h-4" />
                            {t('proforma.back_to_list')}
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-bold text-gray-900">
                        {isDuplicate ? t('proforma.duplicate_title') : t('proforma.create_title')}
                    </h1>
                    <p className="mt-1 text-sm text-gray-500">{t('proforma.create_subtitle')}</p>
                </div>

                <form onSubmit={submit}>
                    {/* Basic Info */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>{t('proforma.basic_info')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="client_id">{t('proforma.client')} *</Label>
                                    <Select value={data.client_id} onValueChange={(v) => setData('client_id', v)}>
                                        <SelectTrigger className="mt-1" error={errors.client_id}>
                                            <SelectValue placeholder={t('proforma.select_client')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {clients.map((c) => (
                                                <SelectItem key={c.id} value={c.id.toString()}>
                                                    {c.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Label htmlFor="currency">{t('proforma.currency')} *</Label>
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
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <Label htmlFor="proforma_prefix">{t('proforma.prefix')}</Label>
                                    <Input
                                        id="proforma_prefix"
                                        value={data.proforma_prefix}
                                        onChange={(e) => setData('proforma_prefix', e.target.value)}
                                        className="mt-1"
                                        placeholder="PRO-"
                                        error={errors.proforma_prefix}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="proforma_sequence">{t('proforma.number')} *</Label>
                                    <Input
                                        id="proforma_sequence"
                                        type="number"
                                        value={data.proforma_sequence}
                                        onChange={(e) => setData('proforma_sequence', parseInt(e.target.value))}
                                        className="mt-1"
                                        error={errors.proforma_sequence}
                                    />
                                </div>
                                <div>
                                    <Label>{t('proforma.year')}</Label>
                                    <Input value={currentYear} disabled className="mt-1" />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="issue_date">{t('proforma.issue_date')} *</Label>
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
                                    <Label htmlFor="valid_until">{t('proforma.valid_until')} *</Label>
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

                    {/* Items */}
                    <Card className="mb-6">
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>{t('proforma.items')}</CardTitle>
                            <Button type="button" variant="outline" size="sm" onClick={addItem}>
                                <Plus className="w-4 h-4 mr-2" />
                                {t('proforma.add_item')}
                            </Button>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {data.items.map((item, index) => (
                                    <div key={index} className="p-4 border rounded-lg bg-gray-50">
                                        <div className="flex justify-between items-start mb-4">
                                            <span className="text-sm font-medium text-gray-500">#{index + 1}</span>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => removeItem(index)}
                                                disabled={data.items.length === 1}
                                            >
                                                <Trash2 className="w-4 h-4 text-destructive" />
                                            </Button>
                                        </div>

                                        {/* Mode selector */}
                                        {articles.length > 0 && (
                                            <div className="flex gap-2 mb-4">
                                                <Button
                                                    type="button"
                                                    variant={!item.is_manual ? 'default' : 'outline'}
                                                    size="sm"
                                                    onClick={() => setItemMode(index, false)}
                                                >
                                                    {t('invoices.select_article')}
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant={item.is_manual ? 'default' : 'outline'}
                                                    size="sm"
                                                    onClick={() => setItemMode(index, true)}
                                                >
                                                    {t('invoices.manual_entry')}
                                                </Button>
                                            </div>
                                        )}

                                        {/* Article selector */}
                                        {!item.is_manual && articles.length > 0 && (
                                            <div className="mb-4">
                                                <Label>{t('proforma.article')}</Label>
                                                <Select
                                                    value={item.article_id}
                                                    onValueChange={(v) => selectArticle(index, v)}
                                                >
                                                    <SelectTrigger className="mt-1">
                                                        <SelectValue placeholder={t('invoices.select_article')} />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {articles.map((a) => (
                                                            <SelectItem key={a.id} value={a.id.toString()}>
                                                                {a.name} - {formatNumber(a.price)} {data.currency}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        )}

                                        {/* Manual entry fields */}
                                        {(item.is_manual || articles.length === 0) && (
                                            <div className="mb-4">
                                                <Label>{t('proforma.description')}</Label>
                                                <Textarea
                                                    value={item.description}
                                                    onChange={(e) => updateItem(index, 'description', e.target.value)}
                                                    placeholder={t('invoices.item_description')}
                                                    className="mt-1"
                                                    rows={3}
                                                />
                                            </div>
                                        )}

                                        {/* Selected article info */}
                                        {!item.is_manual && item.article_id && (
                                            <div className="mb-4 p-3 bg-blue-50 rounded-md">
                                                <p className="text-sm font-medium text-blue-900">{item.description}</p>
                                            </div>
                                        )}

                                        {/* Quantity, Price, Tax, Total */}
                                        <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                                            <div>
                                                <Label>{t('proforma.quantity')}</Label>
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    value={item.quantity}
                                                    onChange={(e) => updateItem(index, 'quantity', parseFloat(e.target.value) || 0)}
                                                    className="mt-1"
                                                />
                                            </div>
                                            <div>
                                                <Label>{t('proforma.unit_price')}</Label>
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    value={item.unit_price}
                                                    onChange={(e) => updateItem(index, 'unit_price', parseFloat(e.target.value) || 0)}
                                                    className="mt-1"
                                                    disabled={!item.is_manual && !!item.article_id}
                                                />
                                            </div>
                                            <div>
                                                <Label>{t('proforma.tax_rate')} %</Label>
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    value={item.tax_rate}
                                                    onChange={(e) => updateItem(index, 'tax_rate', parseFloat(e.target.value) || 0)}
                                                    className="mt-1"
                                                    disabled={!item.is_manual && !!item.article_id}
                                                />
                                            </div>
                                            <div>
                                                <Label>{t('proforma.item_total')}</Label>
                                                <div className="mt-1 h-10 px-3 flex items-center bg-gray-100 rounded-md font-medium text-sm">
                                                    {formatNumber(calculateItemTotal(item), 2)} {data.currency}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            {/* Totals */}
                            <div className="mt-6 pt-6 border-t">
                                <div className="flex justify-end">
                                    <div className="w-64 space-y-2">
                                        <div className="flex justify-between text-sm">
                                            <span className="text-gray-600">{t('proforma.subtotal')}:</span>
                                            <span className="font-medium">{formatNumber(subtotal, 2)} {data.currency}</span>
                                        </div>
                                        <div className="flex justify-between text-sm">
                                            <span className="text-gray-600">{t('proforma.tax')}:</span>
                                            <span className="font-medium">{formatNumber(taxAmount, 2)} {data.currency}</span>
                                        </div>
                                        <div className="flex justify-between text-lg font-bold pt-2 border-t">
                                            <span>{t('proforma.total')}:</span>
                                            <span>{formatNumber(total, 2)} {data.currency}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Notes */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>{t('proforma.notes')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Textarea
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                placeholder={t('proforma.notes_placeholder')}
                                rows={4}
                            />
                        </CardContent>
                    </Card>

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-4">
                        <Button variant="outline" asChild>
                            <Link href="/proforma-invoices">{t('general.cancel')}</Link>
                        </Button>
                        <Button type="submit" disabled={processing} loading={processing}>
                            {t('proforma.save_proforma')}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

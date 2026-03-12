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
import type { ProformaInvoice, Client, Article, Bundle } from '@/types';

type ItemSource = 'article' | 'bundle';

interface FormItem {
    article_id: string;
    bundle_id: string;
    description: string;
    quantity: number;
    unit_price: number;
    tax_rate: number;
    discount: number;
    item_source: ItemSource;
}

interface EditProformaProps {
    proforma: ProformaInvoice;
    clients: Client[];
    articles: Article[];
    bundles?: Bundle[];
}

export default function EditProforma({ proforma, clients, articles, bundles = [] }: EditProformaProps) {
    const { t } = useTranslation();

    const defaultItem: FormItem = { article_id: '', bundle_id: '', description: '', quantity: 1, unit_price: 0, tax_rate: 18, discount: 0, item_source: 'article' };

    const mapExistingItem = (item: any): FormItem => {
        const source: ItemSource = item.bundle_id ? 'bundle' : 'article';
        return {
            article_id: item.article_id?.toString() || '',
            bundle_id: item.bundle_id?.toString() || '',
            description: item.description,
            quantity: item.quantity,
            unit_price: item.unit_price,
            tax_rate: item.tax_rate,
            discount: item.discount ?? 0,
            item_source: source,
        };
    };

    const { data, setData, put, processing, errors } = useForm({
        client_id: proforma.client_id.toString(),
        currency: proforma.currency,
        issue_date: proforma.issue_date,
        valid_until: proforma.valid_until,
        proforma_prefix: proforma.proforma_prefix || '',
        proforma_sequence: proforma.proforma_sequence,
        status: proforma.status,
        notes: proforma.notes || '',
        items: proforma.items?.map(mapExistingItem) || [defaultItem],
    });

    const addItem = () => {
        const client = clients.find(c => c.id.toString() === data.client_id);
        setData('items', [...data.items, { ...defaultItem, discount: client?.discount ?? 0 }]);
    };

    const removeItem = (index: number) => {
        if (data.items.length > 1) {
            setData('items', data.items.filter((_, i) => i !== index));
        }
    };

    const updateItem = (index: number, field: keyof FormItem, value: string | number) => {
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
                bundle_id: '',
                description: article.name,
                unit_price: article.price,
                tax_rate: article.tax_rate,
                item_source: 'article',
            };
            setData('items', items);
        }
    };

    const selectBundle = (index: number, bundleId: string) => {
        const bundle = bundles.find((b) => b.id.toString() === bundleId);
        if (bundle) {
            const items = [...data.items];
            items[index] = {
                ...items[index],
                article_id: '',
                bundle_id: bundleId,
                description: bundle.name,
                unit_price: bundle.price,
                tax_rate: bundle.tax_rate,
                item_source: 'bundle',
            };
            setData('items', items);
        }
    };

    const setItemSource = (index: number, source: ItemSource) => {
        const items = [...data.items];
        items[index] = {
            ...items[index],
            item_source: source,
            article_id: '',
            bundle_id: '',
            description: '',
            unit_price: 0,
            tax_rate: 18,
        };
        setData('items', items);
    };

    const calculateItemTotal = (item: FormItem) => {
        const base = item.quantity * item.unit_price;
        const discounted = base * (1 - (item.discount || 0) / 100);
        const tax = discounted * (item.tax_rate / 100);
        return discounted + tax;
    };

    const subtotal = data.items.reduce((sum, item) => sum + item.quantity * item.unit_price, 0);
    const discountAmount = data.items.reduce((sum, item) => {
        const base = item.quantity * item.unit_price;
        return sum + base * ((item.discount || 0) / 100);
    }, 0);
    const taxAmount = data.items.reduce((sum, item) => {
        const base = item.quantity * item.unit_price;
        const discounted = base * (1 - (item.discount || 0) / 100);
        return sum + discounted * (item.tax_rate / 100);
    }, 0);
    const total = subtotal - discountAmount + taxAmount;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(`/proforma-invoices/${proforma.id}`);
    };

    return (
        <AppLayout>
            <Head title={`${t('proforma.edit_title')} - ${proforma.proforma_number}`} />

            <div className="max-w-4xl mx-auto">
                {/* Header */}
                <div className="mb-6">
                    <Button variant="ghost" size="sm" asChild className="mb-4">
                        <Link href={`/proforma-invoices/${proforma.id}`} className="flex items-center gap-2">
                            <ArrowLeft className="w-4 h-4" />
                            {t('proforma.back_to_proforma')}
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-bold text-gray-900">{t('proforma.edit_title')}</h1>
                    <p className="mt-1 text-sm text-gray-500">{proforma.proforma_number}</p>
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
                                    <Select value={data.client_id} onValueChange={(v) => {
                                        const client = clients.find(c => c.id.toString() === v);
                                        const clientDiscount = client?.discount ?? 0;
                                        setData(prev => ({
                                            ...prev,
                                            client_id: v,
                                            items: prev.items.map(item => ({ ...item, discount: clientDiscount })),
                                        }));
                                    }}>
                                        <SelectTrigger className="mt-1" error={errors.client_id}>
                                            <SelectValue placeholder={t('proforma.select_client')} />
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
                                    <Label htmlFor="status">{t('proforma.status')} *</Label>
                                    <Select value={data.status} onValueChange={(v: ProformaInvoice['status']) => setData('status', v)}>
                                        <SelectTrigger className="mt-1">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="draft">{t('proforma.status_draft')}</SelectItem>
                                            <SelectItem value="sent">{t('proforma.status_sent')}</SelectItem>
                                            <SelectItem value="converted_to_invoice">{t('proforma.status_converted')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
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

                                        {/* Mode selector - only show if bundles exist */}
                                        {bundles.length > 0 && (
                                            <div className="flex flex-wrap gap-2 mb-4">
                                                <Button
                                                    type="button"
                                                    variant={item.item_source === 'article' ? 'default' : 'outline'}
                                                    size="sm"
                                                    onClick={() => setItemSource(index, 'article')}
                                                >
                                                    {t('invoices.select_article')}
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant={item.item_source === 'bundle' ? 'default' : 'outline'}
                                                    size="sm"
                                                    onClick={() => setItemSource(index, 'bundle')}
                                                >
                                                    {t('invoices.select_bundle')}
                                                </Button>
                                            </div>
                                        )}

                                        {/* Article selector */}
                                        {item.item_source === 'article' && (
                                            <div className="mb-4">
                                                <Label>{t('invoices.article')} *</Label>
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

                                        {/* Bundle selector */}
                                        {item.item_source === 'bundle' && (
                                            <div className="mb-4">
                                                <Label>{t('invoices.select_bundle')} *</Label>
                                                <Select
                                                    value={item.bundle_id}
                                                    onValueChange={(v) => selectBundle(index, v)}
                                                >
                                                    <SelectTrigger className="mt-1">
                                                        <SelectValue placeholder={t('invoices.select_bundle')} />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {bundles.map((b) => (
                                                            <SelectItem key={b.id} value={b.id.toString()}>
                                                                {b.name} - {formatNumber(b.price)} {data.currency}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        )}

                                        {/* Quantity, Price, Discount, Tax, Total */}
                                        <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
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
                                                />
                                            </div>
                                            <div>
                                                <Label>{t('proforma.discount')}</Label>
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="100"
                                                    value={item.discount}
                                                    onChange={(e) => updateItem(index, 'discount', parseFloat(e.target.value) || 0)}
                                                    className="mt-1"
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
                                        {discountAmount > 0 && (
                                            <div className="flex justify-between text-sm">
                                                <span className="text-gray-600">{t('proforma.discount')}:</span>
                                                <span className="font-medium text-red-600">-{formatNumber(discountAmount, 2)} {data.currency}</span>
                                            </div>
                                        )}
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
                            <Link href={`/proforma-invoices/${proforma.id}`}>{t('general.cancel')}</Link>
                        </Button>
                        <Button type="submit" disabled={processing} loading={processing}>
                            {t('proforma.update_proforma')}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

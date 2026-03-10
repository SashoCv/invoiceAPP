import { FormEventHandler, useState } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/utils';
import { ArrowLeft, Plus, Trash2, Package } from 'lucide-react';

interface ArticleOption {
    id: number;
    name: string;
    unit: string;
    price: number;
    stock_quantity: number;
}

interface ReceiptItem {
    article_id: string;
    quantity: string;
    cost_price: string;
}

interface Props {
    articles: ArticleOption[];
}

export default function CreateGoodsReceipt({ articles }: Props) {
    const { t } = useTranslation();
    const [items, setItems] = useState<ReceiptItem[]>([
        { article_id: '', quantity: '', cost_price: '' },
    ]);

    const { data, setData, processing, errors, setError, clearErrors } = useForm({
        date: new Date().toISOString().split('T')[0],
        notes: '',
    });
    const [submitting, setSubmitting] = useState(false);

    const addItem = () => {
        setItems([...items, { article_id: '', quantity: '', cost_price: '' }]);
    };

    const removeItem = (index: number) => {
        if (items.length > 1) {
            setItems(items.filter((_, i) => i !== index));
        }
    };

    const updateItem = (index: number, field: keyof ReceiptItem, value: string) => {
        const updated = [...items];
        updated[index] = { ...updated[index], [field]: value };

        // Auto-fill cost_price with selling price when selecting article
        if (field === 'article_id' && value) {
            const article = articles.find((a) => a.id === Number(value));
            if (article && !updated[index].cost_price) {
                updated[index].cost_price = String(article.price);
            }
        }

        setItems(updated);
    };

    const getArticle = (articleId: string) => articles.find((a) => a.id === Number(articleId));

    const getLineTotal = (item: ReceiptItem) => {
        const qty = parseFloat(item.quantity) || 0;
        const price = parseFloat(item.cost_price) || 0;
        return qty * price;
    };

    const totalCost = items.reduce((sum, item) => sum + getLineTotal(item), 0);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        const formItems = items
            .filter((item) => item.article_id && item.quantity && item.cost_price)
            .map((item) => ({
                article_id: Number(item.article_id),
                quantity: parseFloat(item.quantity),
                cost_price: parseFloat(item.cost_price),
            }));

        if (formItems.length === 0) {
            return;
        }

        setSubmitting(true);
        router.post('/goods-receipts', {
            date: data.date,
            notes: data.notes,
            items: formItems,
        }, {
            onError: (errs) => {
                Object.entries(errs).forEach(([key, value]) => setError(key as any, value as string));
                setSubmitting(false);
            },
            onFinish: () => setSubmitting(false),
        });
    };

    // Get used article IDs to filter from selects
    const usedArticleIds = items.map((item) => Number(item.article_id)).filter(Boolean);

    return (
        <AppLayout>
            <Head title={t('inventory.create_goods_receipt')} />

            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Link
                        href="/goods-receipts"
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                    >
                        <ArrowLeft className="w-4 h-4 mr-1" />
                        {t('inventory.back_to_receipts')}
                    </Link>
                </div>

                <form onSubmit={submit}>
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Package className="w-5 h-5" />
                                {t('inventory.create_goods_receipt')}
                            </CardTitle>
                            <CardDescription>{t('inventory.create_goods_receipt_subtitle')}</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="date">{t('inventory.receipt_date')} *</Label>
                                    <Input
                                        id="date"
                                        type="date"
                                        value={data.date}
                                        onChange={(e) => setData('date', e.target.value)}
                                        className="mt-1"
                                        error={errors.date}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="notes">{t('inventory.receipt_notes')}</Label>
                                    <Input
                                        id="notes"
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        className="mt-1"
                                        placeholder={t('inventory.receipt_notes_placeholder')}
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="mb-6">
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle>{t('inventory.receipt_items')}</CardTitle>
                                <Button type="button" variant="outline" size="sm" onClick={addItem}>
                                    <Plus className="w-4 h-4 mr-1" />
                                    {t('inventory.add_item')}
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {/* Header */}
                                <div className="hidden md:grid md:grid-cols-12 gap-3 text-xs font-medium text-gray-500 uppercase tracking-wider px-1">
                                    <div className="col-span-4">{t('inventory.name')}</div>
                                    <div className="col-span-2">{t('inventory.quantity')}</div>
                                    <div className="col-span-2">{t('inventory.cost_price')}</div>
                                    <div className="col-span-2">{t('inventory.selling_price')}</div>
                                    <div className="col-span-1 text-right">{t('inventory.line_total')}</div>
                                    <div className="col-span-1" />
                                </div>

                                {items.map((item, index) => {
                                    const article = getArticle(item.article_id);
                                    const lineTotal = getLineTotal(item);

                                    return (
                                        <div key={index} className="grid grid-cols-1 md:grid-cols-12 gap-3 items-start p-3 bg-gray-50 rounded-lg">
                                            <div className="md:col-span-4">
                                                <Label className="md:hidden text-xs text-gray-500 mb-1">{t('inventory.name')}</Label>
                                                <Select
                                                    value={item.article_id}
                                                    onValueChange={(value) => updateItem(index, 'article_id', value)}
                                                >
                                                    <SelectTrigger error={errors[`items.${index}.article_id`]}>
                                                        <SelectValue placeholder={t('inventory.select_article_placeholder')} />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {articles.map((a) => (
                                                            <SelectItem
                                                                key={a.id}
                                                                value={String(a.id)}
                                                                disabled={usedArticleIds.includes(a.id) && Number(item.article_id) !== a.id}
                                                            >
                                                                {a.name} ({formatNumber(a.stock_quantity)} {a.unit})
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            <div className="md:col-span-2">
                                                <Label className="md:hidden text-xs text-gray-500 mb-1">{t('inventory.quantity')}</Label>
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    min="0.01"
                                                    value={item.quantity}
                                                    onChange={(e) => updateItem(index, 'quantity', e.target.value)}
                                                    placeholder="0"
                                                    error={errors[`items.${index}.quantity`]}
                                                />
                                            </div>
                                            <div className="md:col-span-2">
                                                <Label className="md:hidden text-xs text-gray-500 mb-1">{t('inventory.cost_price')}</Label>
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    value={item.cost_price}
                                                    onChange={(e) => updateItem(index, 'cost_price', e.target.value)}
                                                    placeholder="0.00"
                                                    error={errors[`items.${index}.cost_price`]}
                                                />
                                            </div>
                                            <div className="md:col-span-2">
                                                <Label className="md:hidden text-xs text-gray-500 mb-1">{t('inventory.selling_price')}</Label>
                                                <div className="h-9 flex items-center text-sm text-gray-500 px-3 bg-gray-100 rounded-md">
                                                    {article ? formatNumber(article.price) : '-'}
                                                </div>
                                            </div>
                                            <div className="md:col-span-1">
                                                <Label className="md:hidden text-xs text-gray-500 mb-1">{t('inventory.line_total')}</Label>
                                                <div className="h-9 flex items-center justify-end text-sm font-bold text-gray-900">
                                                    {formatNumber(lineTotal)}
                                                </div>
                                            </div>
                                            <div className="md:col-span-1 flex items-start justify-end">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => removeItem(index)}
                                                    disabled={items.length === 1}
                                                    className="text-red-500 hover:text-red-700 hover:bg-red-50"
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    );
                                })}

                                {errors.items && (
                                    <p className="text-sm text-red-600">{errors.items}</p>
                                )}
                            </div>

                            {/* Total */}
                            <div className="mt-6 pt-4 border-t flex items-center justify-between">
                                <span className="text-lg font-semibold text-gray-900">{t('inventory.total_cost')}</span>
                                <span className="text-2xl font-bold text-gray-900">{formatNumber(totalCost)} MKD</span>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={submitting} loading={submitting} size="lg">
                            {t('inventory.save_receipt')}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

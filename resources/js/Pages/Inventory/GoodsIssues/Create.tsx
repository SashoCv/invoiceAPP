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
import { ArrowLeft, Plus, Trash2, PackageMinus } from 'lucide-react';

interface ArticleOption {
    id: number;
    name: string;
    unit: string;
    price: number;
    stock_quantity: number;
}

interface IssueItem {
    article_id: string;
    quantity: string;
}

interface ClientOption {
    id: number;
    name: string;
    company: string | null;
}

interface Props {
    articles: ArticleOption[];
    clients: ClientOption[];
}

export default function CreateGoodsIssue({ articles, clients }: Props) {
    const { t } = useTranslation();
    const [items, setItems] = useState<IssueItem[]>([
        { article_id: '', quantity: '' },
    ]);

    const { data, setData, errors, setError } = useForm({
        date: new Date().toISOString().split('T')[0],
        notes: '',
        client_id: '' as string,
    });
    const [submitting, setSubmitting] = useState(false);

    const addItem = () => {
        setItems([...items, { article_id: '', quantity: '' }]);
    };

    const removeItem = (index: number) => {
        if (items.length > 1) {
            setItems(items.filter((_, i) => i !== index));
        }
    };

    const updateItem = (index: number, field: keyof IssueItem, value: string) => {
        const updated = [...items];
        updated[index] = { ...updated[index], [field]: value };
        setItems(updated);
    };

    const getArticle = (articleId: string) => articles.find((a) => a.id === Number(articleId));

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        const formItems = items
            .filter((item) => item.article_id && item.quantity)
            .map((item) => ({
                article_id: Number(item.article_id),
                quantity: parseFloat(item.quantity),
            }));

        if (formItems.length === 0) {
            return;
        }

        setSubmitting(true);
        router.post('/goods-issues', {
            date: data.date,
            notes: data.notes,
            client_id: data.client_id ? Number(data.client_id) : null,
            items: formItems,
        }, {
            onError: (errs) => {
                Object.entries(errs).forEach(([key, value]) => setError(key as any, value as string));
                setSubmitting(false);
            },
            onFinish: () => setSubmitting(false),
        });
    };

    const usedArticleIds = items.map((item) => Number(item.article_id)).filter(Boolean);

    return (
        <AppLayout>
            <Head title={t('inventory.create_goods_issue')} />

            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Link
                        href="/goods-issues"
                        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
                    >
                        <ArrowLeft className="w-4 h-4 mr-1" />
                        {t('inventory.back_to_issues')}
                    </Link>
                </div>

                <form onSubmit={submit}>
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <PackageMinus className="w-5 h-5" />
                                {t('inventory.create_goods_issue')}
                            </CardTitle>
                            <CardDescription>{t('inventory.create_goods_issue_subtitle')}</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                                    <Label>{t('inventory.issue_client')}</Label>
                                    <Select
                                        value={data.client_id}
                                        onValueChange={(value) => setData('client_id', value === '_none' ? '' : value)}
                                    >
                                        <SelectTrigger className="mt-1">
                                            <SelectValue placeholder={t('inventory.issue_client_placeholder')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="_none">{t('inventory.no_client')}</SelectItem>
                                            {clients.map((c) => (
                                                <SelectItem key={c.id} value={String(c.id)}>
                                                    {c.company || c.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label htmlFor="notes">{t('inventory.receipt_notes')}</Label>
                                    <Input
                                        id="notes"
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        className="mt-1"
                                        placeholder={t('inventory.issue_notes_placeholder')}
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
                                <div className="hidden md:grid md:grid-cols-[4fr_2fr_2fr_auto] gap-3 text-xs font-medium text-gray-500 uppercase tracking-wider px-1">
                                    <div>{t('inventory.name')}</div>
                                    <div>{t('inventory.quantity')}</div>
                                    <div>{t('inventory.current_stock')}</div>
                                    <div className="w-8" />
                                </div>

                                {items.map((item, index) => {
                                    const article = getArticle(item.article_id);

                                    return (
                                        <div key={index} className="grid grid-cols-1 md:grid-cols-[4fr_2fr_2fr_auto] gap-3 items-start p-3 bg-gray-50 rounded-lg">
                                            <div>
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
                                            <div>
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
                                            <div>
                                                <Label className="md:hidden text-xs text-gray-500 mb-1">{t('inventory.current_stock')}</Label>
                                                <div className="h-9 flex items-center text-sm text-gray-500 px-3 bg-gray-100 rounded-md">
                                                    {article ? `${formatNumber(article.stock_quantity)} ${article.unit}` : '-'}
                                                </div>
                                            </div>
                                            <div className="flex items-start justify-end w-8">
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
                        </CardContent>
                    </Card>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={submitting} loading={submitting} size="lg">
                            {t('inventory.save_issue')}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

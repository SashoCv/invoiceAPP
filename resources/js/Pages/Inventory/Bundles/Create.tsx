import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
import type { Article } from '@/types';

interface CreateBundleProps {
    articles: Article[];
}

interface BundleFormItem {
    article_id: string;
    quantity: number;
}

export default function CreateBundle({ articles }: CreateBundleProps) {
    const { t } = useTranslation();

    const { data, setData, post, processing, errors } = useForm<{
        name: string;
        description: string;
        price: number;
        tax_rate: number;
        items: BundleFormItem[];
    }>({
        name: '',
        description: '',
        price: 0,
        tax_rate: 18,
        items: [{ article_id: '', quantity: 1 }],
    });

    const addComponent = () => {
        setData('items', [...data.items, { article_id: '', quantity: 1 }]);
    };

    const removeComponent = (index: number) => {
        if (data.items.length <= 1) return;
        setData('items', data.items.filter((_, i) => i !== index));
    };

    const updateComponent = (index: number, field: keyof BundleFormItem, value: string | number) => {
        const updated = [...data.items];
        updated[index] = { ...updated[index], [field]: value };
        setData('items', updated);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/bundles');
    };

    return (
        <AppLayout>
            <Head title={t('inventory.create_bundle')} />

            <div className="max-w-2xl mx-auto">
                <div className="mb-6">
                    <Button variant="ghost" size="sm" asChild className="mb-4">
                        <Link href="/inventory" className="flex items-center gap-2">
                            <ArrowLeft className="w-4 h-4" />
                            {t('inventory.back_to_list')}
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-bold text-gray-900">{t('inventory.create_bundle')}</h1>
                    <p className="mt-1 text-sm text-gray-500">{t('inventory.create_bundle_subtitle')}</p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('inventory.bundle_info')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label htmlFor="name">{t('inventory.bundle_name')} *</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="mt-1"
                                    error={errors.name}
                                />
                            </div>

                            <div>
                                <Label htmlFor="description">{t('inventory.description')}</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className="mt-1"
                                    rows={2}
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="price">{t('inventory.price')} *</Label>
                                    <Input
                                        id="price"
                                        type="number"
                                        step="0.01"
                                        value={data.price}
                                        onChange={(e) => setData('price', parseFloat(e.target.value) || 0)}
                                        className="mt-1"
                                        error={errors.price}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="tax_rate">{t('inventory.tax_rate')} (%)</Label>
                                    <Input
                                        id="tax_rate"
                                        type="number"
                                        step="0.01"
                                        value={data.tax_rate}
                                        onChange={(e) => setData('tax_rate', parseFloat(e.target.value) || 0)}
                                        className="mt-1"
                                        error={errors.tax_rate}
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle>{t('inventory.components')}</CardTitle>
                                <Button type="button" variant="outline" size="sm" onClick={addComponent}>
                                    <Plus className="w-4 h-4 mr-1" />
                                    {t('inventory.add_component')}
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {data.items.map((item, index) => (
                                <div key={index} className="flex items-end gap-3">
                                    <div className="flex-1">
                                        {index === 0 && <Label className="mb-1 block">{t('inventory.select_article')}</Label>}
                                        <Select
                                            value={item.article_id}
                                            onValueChange={(val) => updateComponent(index, 'article_id', val)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder={t('inventory.select_article')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {articles.map((article) => (
                                                    <SelectItem key={article.id} value={String(article.id)}>
                                                        {article.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="w-24">
                                        {index === 0 && <Label className="mb-1 block">{t('inventory.component_quantity')}</Label>}
                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            value={item.quantity}
                                            onChange={(e) => updateComponent(index, 'quantity', parseFloat(e.target.value) || 1)}
                                        />
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => removeComponent(index)}
                                        disabled={data.items.length <= 1}
                                        className="text-red-500 hover:text-red-700"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </Button>
                                </div>
                            ))}
                            {errors.items && (
                                <p className="text-sm text-red-600">{errors.items}</p>
                            )}
                        </CardContent>
                    </Card>

                    <div className="flex items-center justify-end gap-4">
                        <Button variant="outline" asChild>
                            <Link href="/inventory">{t('general.cancel')}</Link>
                        </Button>
                        <Button type="submit" disabled={processing} loading={processing}>
                            {t('inventory.save_bundle')}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

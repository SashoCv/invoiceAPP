import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { useTranslation } from '@/hooks/use-translation';
import { ArrowLeft } from 'lucide-react';

export default function CreateArticle() {
    const { t } = useTranslation();

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        unit: 'kom',
        price: 0,
        tax_rate: 18,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/articles');
    };

    return (
        <AppLayout>
            <Head title={t('articles.create_title')} />

            <div className="max-w-2xl mx-auto">
                {/* Header */}
                <div className="mb-6">
                    <Button variant="ghost" size="sm" asChild className="mb-4">
                        <Link href="/articles" className="flex items-center gap-2">
                            <ArrowLeft className="w-4 h-4" />
                            {t('articles.back_to_list')}
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-bold text-gray-900">{t('articles.create_title')}</h1>
                    <p className="mt-1 text-sm text-gray-500">{t('articles.create_subtitle')}</p>
                </div>

                <form onSubmit={submit}>
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('articles.article_info')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label htmlFor="name">{t('articles.name')} *</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="mt-1"
                                    error={errors.name}
                                />
                            </div>

                            <div>
                                <Label htmlFor="description">{t('articles.description')}</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className="mt-1"
                                    rows={3}
                                />
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <Label htmlFor="unit">{t('articles.unit')} *</Label>
                                    <Input
                                        id="unit"
                                        value={data.unit}
                                        onChange={(e) => setData('unit', e.target.value)}
                                        className="mt-1"
                                        placeholder="kom, час, kg..."
                                        error={errors.unit}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="price">{t('articles.price')} *</Label>
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
                                    <Label htmlFor="tax_rate">{t('articles.tax_rate')} *</Label>
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

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-4 mt-6">
                        <Button variant="outline" asChild>
                            <Link href="/articles">{t('general.cancel')}</Link>
                        </Button>
                        <Button type="submit" disabled={processing} loading={processing}>
                            {t('articles.save_article')}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

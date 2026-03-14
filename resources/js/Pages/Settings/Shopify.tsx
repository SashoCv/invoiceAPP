import { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import SettingsLayout from '@/Components/SettingsLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import {
    CheckCircle,
    XCircle,
    Trash2,
    RefreshCw,
    Wand2,
    Plus,
    Loader2,
    ShoppingBag,
    Unlink,
} from 'lucide-react';

interface ShopifyConnection {
    id: number;
    shop_domain: string;
    is_active: boolean;
    last_synced_at: string | null;
}

interface ShopifyMapping {
    id: number;
    shopify_product_title: string;
    shopify_variant_title: string | null;
    shopify_sku: string | null;
    shopify_variant_id: number;
    shopify_product_id: number;
    article_id: number;
    article?: { id: number; name: string; sku: string | null; unit: string };
}

interface ShopifyProduct {
    id: number;
    title: string;
    variants: {
        id: number;
        title: string;
        sku: string | null;
        price: string;
    }[];
}

interface ArticleOption {
    id: number;
    name: string;
    sku: string | null;
    unit: string;
}

interface Props {
    connection: ShopifyConnection | null;
    mappings: ShopifyMapping[];
    articles: ArticleOption[];
    callbackUrl: string;
}

export default function Shopify({ connection, mappings, articles, callbackUrl }: Props) {
    const { t } = useTranslation();
    const [products, setProducts] = useState<ShopifyProduct[]>([]);
    const [loadingProducts, setLoadingProducts] = useState(false);
    const [selectedVariant, setSelectedVariant] = useState<{
        productId: number;
        variantId: number;
        productTitle: string;
        variantTitle: string | null;
        sku: string | null;
    } | null>(null);
    const [selectedArticleId, setSelectedArticleId] = useState<string>('');

    const connectForm = useForm({
        shop_domain: '',
        client_id: '',
        client_secret: '',
    });

    const handleConnect = (e: React.FormEvent) => {
        e.preventDefault();
        connectForm.post('/settings/shopify/connect');
    };

    const handleDisconnect = () => {
        if (confirm(t('shopify.disconnect_confirm'))) {
            router.post('/settings/shopify/disconnect');
        }
    };

    const handleSync = () => {
        router.post('/settings/shopify/sync');
    };

    const handleAutoMatch = () => {
        router.post('/settings/shopify/auto-match');
    };

    const fetchProducts = async () => {
        setLoadingProducts(true);
        try {
            const response = await fetch('/settings/shopify/products', {
                headers: { Accept: 'application/json' },
            });
            const data = await response.json();
            setProducts(data.products || []);
        } catch {
            // handled by flash
        }
        setLoadingProducts(false);
    };

    const handleSaveMapping = () => {
        if (!selectedVariant || !selectedArticleId) return;

        router.post('/settings/shopify/mappings', {
            shopify_product_id: selectedVariant.productId,
            shopify_variant_id: selectedVariant.variantId,
            shopify_product_title: selectedVariant.productTitle,
            shopify_variant_title: selectedVariant.variantTitle,
            shopify_sku: selectedVariant.sku,
            article_id: parseInt(selectedArticleId),
        }, {
            onSuccess: () => {
                setSelectedVariant(null);
                setSelectedArticleId('');
            },
        });
    };

    const handleDeleteMapping = (id: number) => {
        router.delete(`/settings/shopify/mappings/${id}`);
    };

    return (
        <SettingsLayout>
            <Head title={t('shopify.title')} />

            <div className="space-y-6">
                {/* Connection Section */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <ShoppingBag className="w-5 h-5" />
                            {t('shopify.connection')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {connection ? (
                            <div className="space-y-4">
                                <div className="flex items-center gap-3">
                                    <CheckCircle className="w-5 h-5 text-green-500" />
                                    <div>
                                        <p className="font-medium text-green-700">{t('shopify.connected')}</p>
                                        <p className="text-sm text-gray-500">{connection.shop_domain}</p>
                                    </div>
                                </div>

                                {connection.last_synced_at && (
                                    <p className="text-sm text-gray-500">
                                        {t('shopify.last_synced')}: {new Date(connection.last_synced_at).toLocaleString()}
                                    </p>
                                )}

                                <div className="flex gap-2">
                                    <Button size="sm" onClick={handleSync}>
                                        <RefreshCw className="w-4 h-4 mr-2" />
                                        {t('shopify.sync_orders')}
                                    </Button>
                                    <Button size="sm" variant="destructive" onClick={handleDisconnect}>
                                        <Unlink className="w-4 h-4 mr-2" />
                                        {t('shopify.disconnect')}
                                    </Button>
                                </div>
                            </div>
                        ) : (
                            <form onSubmit={handleConnect} className="space-y-4">
                                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
                                    <p className="font-medium mb-2">{t('shopify.setup_instructions_title')}</p>
                                    <ol className="list-decimal list-inside space-y-1">
                                        <li>{t('shopify.setup_step_1')}</li>
                                        <li>{t('shopify.setup_step_2')}</li>
                                        <li>{t('shopify.setup_step_3')}</li>
                                        <li>{t('shopify.setup_step_4')} <code className="bg-blue-100 px-1.5 py-0.5 rounded text-xs font-mono select-all">{callbackUrl}</code></li>
                                    </ol>
                                </div>

                                <div>
                                    <Label htmlFor="shop_domain">{t('shopify.shop_domain')}</Label>
                                    <Input
                                        id="shop_domain"
                                        placeholder="your-store.myshopify.com"
                                        value={connectForm.data.shop_domain}
                                        onChange={(e) => connectForm.setData('shop_domain', e.target.value)}
                                        className="mt-1"
                                        error={connectForm.errors.shop_domain}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="client_id">{t('shopify.client_id')}</Label>
                                    <Input
                                        id="client_id"
                                        value={connectForm.data.client_id}
                                        onChange={(e) => connectForm.setData('client_id', e.target.value)}
                                        className="mt-1"
                                        error={connectForm.errors.client_id}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="client_secret">{t('shopify.client_secret')}</Label>
                                    <Input
                                        id="client_secret"
                                        type="password"
                                        value={connectForm.data.client_secret}
                                        onChange={(e) => connectForm.setData('client_secret', e.target.value)}
                                        className="mt-1"
                                        error={connectForm.errors.client_secret}
                                    />
                                </div>

                                <Button type="submit" disabled={connectForm.processing} loading={connectForm.processing}>
                                    <ShoppingBag className="w-4 h-4 mr-2" />
                                    {t('shopify.connect_button')}
                                </Button>
                            </form>
                        )}
                    </CardContent>
                </Card>

                {/* Product Mapping Section */}
                {connection && (
                    <Card>
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle>{t('shopify.product_mapping')}</CardTitle>
                                <div className="flex gap-2">
                                    <Button size="sm" variant="outline" onClick={handleAutoMatch}>
                                        <Wand2 className="w-4 h-4 mr-2" />
                                        {t('shopify.auto_match')}
                                    </Button>
                                    <Button size="sm" variant="outline" onClick={fetchProducts} disabled={loadingProducts}>
                                        {loadingProducts ? <Loader2 className="w-4 h-4 mr-2 animate-spin" /> : <Plus className="w-4 h-4 mr-2" />}
                                        {t('shopify.fetch_products')}
                                    </Button>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            {/* Add mapping form */}
                            {products.length > 0 && (
                                <div className="mb-6 p-4 bg-gray-50 rounded-lg space-y-3">
                                    <p className="text-sm font-medium">{t('shopify.add_mapping')}</p>
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <Select
                                            value={selectedVariant ? `${selectedVariant.productId}:${selectedVariant.variantId}` : ''}
                                            onValueChange={(val) => {
                                                const [pId, vId] = val.split(':').map(Number);
                                                const product = products.find(p => p.id === pId);
                                                const variant = product?.variants.find(v => v.id === vId);
                                                if (product && variant) {
                                                    setSelectedVariant({
                                                        productId: pId,
                                                        variantId: vId,
                                                        productTitle: product.title,
                                                        variantTitle: variant.title !== 'Default Title' ? variant.title : null,
                                                        sku: variant.sku || null,
                                                    });
                                                }
                                            }}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder={t('shopify.select_product')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {products.map((product) =>
                                                    product.variants
                                                        .filter((variant) => !mappings.some((m) => m.shopify_variant_id === variant.id))
                                                        .map((variant) => (
                                                            <SelectItem key={variant.id} value={`${product.id}:${variant.id}`}>
                                                                {product.title}{variant.title !== 'Default Title' ? ` - ${variant.title}` : ''}
                                                                {variant.sku ? ` (${variant.sku})` : ''}
                                                            </SelectItem>
                                                        ))
                                                )}
                                            </SelectContent>
                                        </Select>

                                        <Select value={selectedArticleId} onValueChange={setSelectedArticleId}>
                                            <SelectTrigger>
                                                <SelectValue placeholder={t('shopify.select_article')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {articles.map((article) => (
                                                    <SelectItem key={article.id} value={String(article.id)}>
                                                        {article.name}{article.sku ? ` (${article.sku})` : ''}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>

                                        <Button onClick={handleSaveMapping} disabled={!selectedVariant || !selectedArticleId}>
                                            {t('shopify.save_mapping')}
                                        </Button>
                                    </div>
                                </div>
                            )}

                            {/* Existing mappings table */}
                            {mappings.length > 0 ? (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{t('shopify.shopify_product')}</TableHead>
                                            <TableHead>{t('shopify.shopify_sku')}</TableHead>
                                            <TableHead>{t('shopify.local_article')}</TableHead>
                                            <TableHead className="w-[80px]" />
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {mappings.map((mapping) => (
                                            <TableRow key={mapping.id}>
                                                <TableCell>
                                                    {mapping.shopify_product_title}
                                                    {mapping.shopify_variant_title && (
                                                        <span className="text-gray-500 ml-1">
                                                            - {mapping.shopify_variant_title}
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-gray-500">
                                                    {mapping.shopify_sku || '-'}
                                                </TableCell>
                                                <TableCell>{mapping.article?.name || '-'}</TableCell>
                                                <TableCell>
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() => handleDeleteMapping(mapping.id)}
                                                    >
                                                        <Trash2 className="w-4 h-4 text-red-500" />
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            ) : (
                                <div className="text-center py-8">
                                    <XCircle className="w-12 h-12 text-gray-300 mx-auto mb-3" />
                                    <p className="text-gray-500">{t('shopify.no_mappings')}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </SettingsLayout>
    );
}

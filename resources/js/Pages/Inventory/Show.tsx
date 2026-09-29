import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/Components/ui/dialog';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber, formatDate } from '@/lib/utils';
import { ArrowLeft, PackagePlus } from 'lucide-react';
import type { Article, StockMovement } from '@/types';

interface ShowProps {
    item: Article;
    movements: StockMovement[];
    avgCost: number | null;
}

const REASONS_IN = ['opening', 'surplus', 'other'];
const REASONS_OUT = ['shortage', 'writeoff', 'other'];
const todayStr = () => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

function StockStatusBadge({ status, t }: { status: string; t: (key: string) => string }) {
    const variants: Record<string, string> = {
        in_stock: 'bg-green-100 text-green-800',
        low_stock: 'bg-yellow-100 text-yellow-800',
        out_of_stock: 'bg-red-100 text-red-800',
    };
    const labels: Record<string, string> = {
        in_stock: t('inventory.in_stock'),
        low_stock: t('inventory.low_stock'),
        out_of_stock: t('inventory.out_of_stock'),
    };

    return (
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${variants[status] || ''}`}>
            {labels[status] || status}
        </span>
    );
}

function MovementTypeBadge({ type, t }: { type: string; t: (key: string) => string }) {
    const variants: Record<string, string> = {
        receipt: 'bg-green-100 text-green-800',
        issue: 'bg-orange-100 text-orange-800',
        adjustment: 'bg-blue-100 text-blue-800',
        invoice_deduction: 'bg-purple-100 text-purple-800',
    };
    const labels: Record<string, string> = {
        receipt: t('inventory.type_receipt'),
        issue: t('inventory.type_issue'),
        adjustment: t('inventory.type_adjustment'),
        invoice_deduction: t('inventory.type_invoice_deduction'),
    };

    return (
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${variants[type] || ''}`}>
            {labels[type] || type}
        </span>
    );
}

export default function ShowInventoryItem({ item, movements, avgCost }: ShowProps) {
    const { t } = useTranslation();
    const [adjustOpen, setAdjustOpen] = useState(false);

    const adjustForm = useForm({
        type: 'receipt',
        quantity: 0,
        notes: '',
        reason: 'surplus',
        date: todayStr(),
        cost_price: avgCost !== null ? String(avgCost) : '',
    });

    // Direction of the change: Прием adds, Издавање removes, Корекција sets the quantity
    const change = adjustForm.data.type === 'receipt'
        ? adjustForm.data.quantity
        : adjustForm.data.type === 'issue'
            ? -adjustForm.data.quantity
            : adjustForm.data.quantity - Number(item.stock_quantity);
    // Прием is always an input and Издавање an output; Корекција depends on the new quantity
    const isInput = adjustForm.data.type === 'receipt' || (adjustForm.data.type === 'adjustment' && change > 0);
    const reasons = isInput ? REASONS_IN : REASONS_OUT;
    const reason = reasons.includes(adjustForm.data.reason) ? adjustForm.data.reason : reasons[isInput ? 1 : 0];
    const costRequired = isInput && avgCost === null;

    const reasonLabel = (r?: string | null) => (r ? t(`inventory.reason_${r}`) : '');

    const handleAdjust = (e: React.FormEvent) => {
        e.preventDefault();
        adjustForm.transform((data) => ({
            ...data,
            reason,
            cost_price: isInput && data.cost_price !== '' ? data.cost_price : null,
        }));
        adjustForm.post(`/inventory/${item.id}/adjust-stock`, {
            onSuccess: () => {
                setAdjustOpen(false);
                adjustForm.reset();
            },
        });
    };

    return (
        <AppLayout>
            <Head title={item.name} />

            <div className="max-w-4xl mx-auto">
                <div className="mb-6">
                    <Button variant="ghost" size="sm" asChild className="mb-4">
                        <Link href="/inventory" className="flex items-center gap-2">
                            <ArrowLeft className="w-4 h-4" />
                            {t('inventory.back_to_list')}
                        </Link>
                    </Button>

                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">{item.name}</h1>
                            {item.description && (
                                <p className="mt-1 text-sm text-gray-500">{item.description}</p>
                            )}
                        </div>
                        <Button variant="outline" onClick={() => setAdjustOpen(true)}>
                            <PackagePlus className="w-4 h-4 mr-2" />
                            {t('inventory.adjust_stock')}
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-sm text-gray-500">{t('inventory.current_stock')}</div>
                            <div className="mt-1 text-3xl font-bold text-gray-900">
                                {formatNumber(item.stock_quantity, 0)}
                            </div>
                            <div className="mt-1 text-sm text-gray-500">{item.unit}</div>
                            <div className="mt-2">
                                <StockStatusBadge status={item.stock_status} t={t} />
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-sm text-gray-500">{t('inventory.price')}</div>
                            <div className="mt-1 text-3xl font-bold text-gray-900">
                                {formatNumber(item.price, 4)}
                            </div>
                            <div className="mt-1 text-sm text-gray-500">
                                {t('inventory.tax_rate')}: {formatNumber(item.tax_rate)}%
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-sm text-gray-500">{t('inventory.low_stock_threshold')}</div>
                            <div className="mt-1 text-3xl font-bold text-gray-900">
                                {formatNumber(item.low_stock_threshold, 0)}
                            </div>
                            <div className="mt-1 text-sm text-gray-500">{item.unit}</div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('inventory.stock_history')}</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {movements.length === 0 ? (
                            <div className="py-12 text-center text-gray-500">
                                {t('inventory.no_movements')}
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{t('inventory.movement_date')}</TableHead>
                                        <TableHead>{t('inventory.movement_type')}</TableHead>
                                        <TableHead className="text-right">{t('inventory.movement_quantity')}</TableHead>
                                        <TableHead className="text-right">{t('inventory.movement_before')}</TableHead>
                                        <TableHead className="text-right">{t('inventory.movement_after')}</TableHead>
                                        <TableHead>{t('inventory.movement_notes')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {movements.map((movement) => (
                                        <TableRow key={movement.id}>
                                            <TableCell className="text-gray-500 whitespace-nowrap">
                                                {formatDate(movement.document_date || movement.created_at)}
                                            </TableCell>
                                            <TableCell>
                                                <MovementTypeBadge type={movement.type} t={t} />
                                                {movement.reason && (
                                                    <span className="ml-1.5 text-xs text-gray-500">{reasonLabel(movement.reason)}</span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <span className={`font-medium ${movement.quantity < 0 ? 'text-red-600' : 'text-green-600'}`}>
                                                    {movement.quantity > 0 ? '+' : ''}{formatNumber(movement.quantity, 0)}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-right text-gray-500">
                                                {formatNumber(movement.quantity_before, 0)}
                                            </TableCell>
                                            <TableCell className="text-right text-gray-500">
                                                {formatNumber(movement.quantity_after, 0)}
                                            </TableCell>
                                            <TableCell className="text-gray-500 max-w-xs truncate">
                                                {movement.notes || '-'}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Stock Adjustment Dialog */}
            <Dialog open={adjustOpen} onOpenChange={setAdjustOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('inventory.adjust_stock')}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={handleAdjust} className="space-y-4">
                        <div>
                            <Label>{t('inventory.adjustment_type')}</Label>
                            <Select
                                value={adjustForm.data.type}
                                onValueChange={(val) => adjustForm.setData('type', val)}
                            >
                                <SelectTrigger className="mt-1">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="receipt">{t('inventory.receipt')}</SelectItem>
                                    <SelectItem value="issue">{t('inventory.issue')}</SelectItem>
                                    <SelectItem value="adjustment">{t('inventory.adjustment')}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label>{t('inventory.quantity')}</Label>
                            <Input
                                type="number"
                                step="1"
                                min="0"
                                value={adjustForm.data.quantity}
                                onChange={(e) => adjustForm.setData('quantity', parseFloat(e.target.value) || 0)}
                                className="mt-1"
                                error={adjustForm.errors.quantity}
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <Label>{t('inventory.reason')}</Label>
                                <Select value={reason} onValueChange={(val) => adjustForm.setData('reason', val)}>
                                    <SelectTrigger className="mt-1">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {reasons.map((r) => (
                                            <SelectItem key={r} value={r}>{reasonLabel(r)}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label>{t('inventory.document_date')}</Label>
                                <Input
                                    type="date"
                                    max={todayStr()}
                                    value={adjustForm.data.date}
                                    onChange={(e) => adjustForm.setData('date', e.target.value)}
                                    className="mt-1"
                                    error={adjustForm.errors.date}
                                />
                            </div>
                        </div>
                        {isInput && (
                            <div>
                                <Label>
                                    {t('inventory.purchase_price')}
                                    {costRequired && <span className="text-red-500"> *</span>}
                                </Label>
                                <Input
                                    type="number"
                                    step="0.0001"
                                    min="0"
                                    required={costRequired}
                                    value={adjustForm.data.cost_price}
                                    onChange={(e) => adjustForm.setData('cost_price', e.target.value)}
                                    className="mt-1"
                                    error={adjustForm.errors.cost_price}
                                />
                                <p className="mt-1 text-xs text-gray-500">
                                    {costRequired ? t('inventory.purchase_price_required') : t('inventory.purchase_price_hint')}
                                </p>
                            </div>
                        )}
                        <div>
                            <Label>{t('inventory.notes')}</Label>
                            <Textarea
                                value={adjustForm.data.notes}
                                onChange={(e) => adjustForm.setData('notes', e.target.value)}
                                className="mt-1"
                                rows={2}
                                placeholder={t('inventory.notes_placeholder')}
                            />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setAdjustOpen(false)}>
                                {t('general.cancel')}
                            </Button>
                            <Button type="submit" disabled={adjustForm.processing} loading={adjustForm.processing}>
                                {t('general.confirm')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

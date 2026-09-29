import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { useTranslation } from '@/hooks/use-translation';

export type ReceiptType = 'purchase' | 'customer_return' | 'opening';

export interface ReceiptTypeData {
    type: ReceiptType;
    invoice_id: string;
    dependent_costs: string;
}

interface Props {
    data: ReceiptTypeData;
    setData: (field: keyof ReceiptTypeData, value: string) => void;
    invoices: { id: number; label: string }[];
    errors: Partial<Record<keyof ReceiptTypeData, string>>;
}

/**
 * Type of a goods receipt: набавка (with зависни трошоци), поврат од купувач (optionally
 * linked to the invoice, so the goods come back at the cost they left at) or почетна состојба.
 */
export default function ReceiptTypeFields({ data, setData, invoices, errors }: Props) {
    const { t } = useTranslation();

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <Label>{t('inventory.receipt_type')}</Label>
                <Select value={data.type} onValueChange={(v) => setData('type', v)}>
                    <SelectTrigger className="mt-1">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="purchase">{t('inventory.receipt_type_purchase')}</SelectItem>
                        <SelectItem value="customer_return">{t('inventory.receipt_type_customer_return')}</SelectItem>
                        <SelectItem value="opening">{t('inventory.receipt_type_opening')}</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            {data.type === 'purchase' && (
                <div>
                    <Label>{t('inventory.dependent_costs')}</Label>
                    <Input
                        type="number"
                        step="0.01"
                        min="0"
                        value={data.dependent_costs}
                        onChange={(e) => setData('dependent_costs', e.target.value)}
                        className="mt-1"
                        error={errors.dependent_costs}
                    />
                    <p className="mt-1 text-xs text-gray-500">{t('inventory.dependent_costs_hint')}</p>
                </div>
            )}

            {data.type === 'customer_return' && (
                <div>
                    <Label>{t('inventory.return_invoice')}</Label>
                    <Select value={data.invoice_id || '__none__'} onValueChange={(v) => setData('invoice_id', v === '__none__' ? '' : v)}>
                        <SelectTrigger className="mt-1">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="__none__">{t('inventory.return_invoice_none')}</SelectItem>
                            {invoices.map((i) => (
                                <SelectItem key={i.id} value={String(i.id)}>{i.label}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <p className="mt-1 text-xs text-gray-500">{t('inventory.return_invoice_hint')}</p>
                </div>
            )}

            {data.type === 'opening' && (
                <p className="self-end text-xs text-gray-500">{t('inventory.receipt_type_opening_hint')}</p>
            )}
        </div>
    );
}

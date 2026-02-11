import { useState } from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { useTranslation } from '@/hooks/use-translation';
import { formatNumber } from '@/lib/utils';
import { ArrowRightLeft, Calculator } from 'lucide-react';

interface CurrencyCalculatorProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

const CURRENCIES = ['MKD', 'EUR', 'USD', 'GBP', 'CHF'];

export default function CurrencyCalculator({ open, onOpenChange }: CurrencyCalculatorProps) {
    const { t } = useTranslation();
    const [amount, setAmount] = useState('1000');
    const [from, setFrom] = useState('EUR');
    const [to, setTo] = useState('MKD');
    const [date, setDate] = useState(new Date().toISOString().split('T')[0]);
    const [result, setResult] = useState<number | null>(null);
    const [rate, setRate] = useState<number | null>(null);
    const [loading, setLoading] = useState(false);

    const handleConvert = async () => {
        setLoading(true);
        try {
            // Read XSRF-TOKEN from cookie (set automatically by Laravel)
            const xsrfToken = document.cookie
                .split('; ')
                .find(row => row.startsWith('XSRF-TOKEN='))
                ?.split('=')[1];

            const response = await fetch('/currency/convert', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': xsrfToken ? decodeURIComponent(xsrfToken) : '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ amount: parseFloat(amount), from, to, date }),
            });
            const data = await response.json();
            setResult(data.result);
            setRate(data.rate);
        } catch {
            setResult(null);
            setRate(null);
        } finally {
            setLoading(false);
        }
    };

    const handleSwap = () => {
        setFrom(to);
        setTo(from);
        setResult(null);
        setRate(null);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Calculator className="w-5 h-5" />
                        {t('navigation.currency_calculator')}
                    </DialogTitle>
                    <DialogDescription>
                        {t('navigation.currency_calculator_desc')}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    {/* Amount */}
                    <div>
                        <Label>{t('navigation.calc_amount')}</Label>
                        <Input
                            type="number"
                            value={amount}
                            onChange={(e) => { setAmount(e.target.value); setResult(null); }}
                            className="mt-1"
                            min="0"
                            step="any"
                        />
                    </div>

                    {/* From / Swap / To */}
                    <div className="flex items-end gap-2">
                        <div className="flex-1">
                            <Label>{t('navigation.calc_from')}</Label>
                            <Select value={from} onValueChange={(v) => { setFrom(v); setResult(null); }}>
                                <SelectTrigger className="mt-1">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {CURRENCIES.map((c) => (
                                        <SelectItem key={c} value={c}>{c}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            className="shrink-0 mb-[1px]"
                            onClick={handleSwap}
                        >
                            <ArrowRightLeft className="w-4 h-4" />
                        </Button>

                        <div className="flex-1">
                            <Label>{t('navigation.calc_to')}</Label>
                            <Select value={to} onValueChange={(v) => { setTo(v); setResult(null); }}>
                                <SelectTrigger className="mt-1">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {CURRENCIES.map((c) => (
                                        <SelectItem key={c} value={c}>{c}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    {/* Date */}
                    <div>
                        <Label>{t('navigation.calc_date')}</Label>
                        <Input
                            type="date"
                            value={date}
                            onChange={(e) => { setDate(e.target.value); setResult(null); }}
                            className="mt-1"
                        />
                    </div>

                    {/* Convert Button */}
                    <Button onClick={handleConvert} disabled={loading || !amount} className="w-full">
                        {loading ? t('navigation.calc_converting') : t('navigation.calc_convert')}
                    </Button>

                    {/* Result */}
                    {result !== null && (
                        <div className="rounded-lg bg-gray-50 border p-4 text-center">
                            <p className="text-sm text-gray-500 mb-1">
                                {formatNumber(parseFloat(amount), 2)} {from} =
                            </p>
                            <p className="text-2xl font-bold text-gray-900">
                                {formatNumber(result, 2)} {to}
                            </p>
                            {rate !== null && (
                                <p className="text-xs text-gray-400 mt-2">
                                    1 {from} = {formatNumber(rate, 4)} {to}
                                </p>
                            )}
                        </div>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}

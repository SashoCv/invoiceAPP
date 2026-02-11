import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function formatCurrency(amount: number, currency: string = 'MKD'): string {
    return new Intl.NumberFormat('mk-MK', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
    }).format(amount);
}

export function formatDate(date: string): string {
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}.${month}.${year}`;
}

export function formatNumber(num: number, decimals: number = 2): string {
    return new Intl.NumberFormat('mk-MK', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }).format(num);
}

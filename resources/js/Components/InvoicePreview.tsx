import { useTranslation } from '@/hooks/use-translation';
import { formatDate, formatNumber } from '@/lib/utils';
import type { Invoice, ProformaInvoice, Offer, Agency, BankAccount } from '@/types';

interface InvoicePreviewProps {
    document: Invoice | ProformaInvoice | Offer;
    type: 'invoice' | 'proforma' | 'offer';
    agency?: Agency | null;
    bankAccount?: BankAccount | null;
    template?: 'classic' | 'modern' | 'minimal' | 'formal';
}

const getCurrencySymbol = (currency: string): string => {
    const symbols: Record<string, string> = {
        MKD: 'ден.',
        EUR: '€',
        USD: '$',
        GBP: '£',
        CHF: 'CHF',
    };
    return symbols[currency] || currency;
};

const getCurrencyDecimals = (currency: string): number => currency === 'MKD' ? 0 : 2;

const getStatusLabel = (status: string): string => {
    const labels: Record<string, string> = {
        draft: 'Нацрт',
        sent: 'Испратена',
        unpaid: 'Неплатена',
        paid: 'Платена',
        overdue: 'Задоцнета',
        cancelled: 'Откажана',
        converted_to_invoice: 'Конвертирана',
        accepted: 'Прифатена',
        rejected: 'Одбиена',
    };
    return labels[status] || status;
};

const titles: Record<string, string> = {
    invoice: 'ФАКТУРА',
    proforma: 'ПРОФАКТУРА',
    offer: 'ПОНУДА',
};

const templateNames: Record<string, string> = {
    classic: 'Classic Template',
    modern: 'Modern Template',
    minimal: 'Minimal Template',
    formal: 'Formal Template',
};

// Helper to check if document is an Offer
const isOffer = (doc: Invoice | ProformaInvoice | Offer): doc is Offer => {
    return 'offer_number' in doc;
};

// ============ CLASSIC TEMPLATE ============
function ClassicTemplate({ document, type, agency, bankAccount }: Omit<InvoicePreviewProps, 'template'>) {
    const currencySymbol = getCurrencySymbol(document.currency);
    const offer = isOffer(document) ? document : null;
    const hasItems = offer ? offer.has_items : true;

    const getDocumentNumber = () => {
        if ('invoice_number' in document) return document.invoice_number;
        if ('proforma_number' in document) return document.proforma_number;
        if ('offer_number' in document) return document.offer_number;
        return '';
    };

    const getDueDate = () => {
        if ('due_date' in document) return document.due_date;
        if ('valid_until' in document) return document.valid_until;
        return null;
    };

    const dueDateLabel = type === 'invoice' ? 'Датум на доспевање' : 'Важи до';

    return (
        <div className="p-8" style={{ fontFamily: 'system-ui, -apple-system, sans-serif' }}>
            {/* Header */}
            <div className="flex justify-between items-start mb-8 pb-4 border-b-2 border-blue-800">
                <div>
                    {agency?.name && (
                        <h2 className="text-xl font-bold text-blue-800 mb-2">{agency.name}</h2>
                    )}
                    <div className="text-sm text-gray-500 space-y-0.5">
                        {agency?.address && <div>{agency.address}</div>}
                        {agency?.city && <div>{agency.postal_code && `${agency.postal_code} `}{agency.city}</div>}
                        {agency?.phone && <div>Тел: {agency.phone}</div>}
                        {agency?.email && <div>Email: {agency.email}</div>}
                        {agency?.tax_number && <div>ЕДБ: {agency.tax_number}</div>}
                    </div>
                </div>
                <div className="text-right">
                    <h1 className="text-3xl font-bold text-blue-800">{titles[type]}</h1>
                    <div className="text-gray-500 mt-1">Бр. {getDocumentNumber()}</div>
                </div>
            </div>

            {/* Offer Title */}
            {offer?.title && (
                <div className="mb-6">
                    <h2 className="text-xl font-semibold text-gray-900">{offer.title}</h2>
                </div>
            )}

            {/* Info Section */}
            <div className="grid grid-cols-2 gap-8 mb-8">
                <div>
                    <h3 className="text-sm font-bold text-blue-800 mb-3 uppercase tracking-wide">Клиент</h3>
                    <div className="font-semibold text-gray-900">{document.client?.company || document.client?.name}</div>
                    <div className="text-sm text-gray-500 mt-1 space-y-0.5">
                        {document.client?.address && <div>{document.client.address}</div>}
                        {document.client?.city && <div>{document.client.postal_code && `${document.client.postal_code} `}{document.client.city}</div>}
                        {document.client?.tax_number && <div>ЕДБ: {document.client.tax_number}</div>}
                    </div>
                </div>
                <div>
                    <h3 className="text-sm font-bold text-blue-800 mb-3 uppercase tracking-wide">Детали</h3>
                    <table className="text-sm">
                        <tbody>
                            <tr><td className="text-gray-500 pr-4 py-0.5">Датум:</td><td className="font-medium">{formatDate(document.issue_date)}</td></tr>
                            {getDueDate() && <tr><td className="text-gray-500 pr-4 py-0.5">{dueDateLabel}:</td><td className="font-medium">{formatDate(getDueDate())}</td></tr>}
                            <tr><td className="text-gray-500 pr-4 py-0.5">Валута:</td><td className="font-medium">{document.currency}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Offer Content (when no items) */}
            {offer && !hasItems && offer.content && (
                <div className="mb-8 p-6 bg-gray-50 rounded-lg border border-gray-200">
                    <h3 className="text-sm font-bold text-blue-800 mb-3 uppercase tracking-wide">Опис на понудата</h3>
                    <div
                        className="text-sm text-gray-700 leading-relaxed prose prose-sm max-w-none"
                        dangerouslySetInnerHTML={{ __html: offer.content }}
                    />
                </div>
            )}

            {/* Items Table - only show if has items */}
            {hasItems && document.items && document.items.length > 0 && (
                <div className="mb-6">
                    <table className="w-full">
                        <thead>
                            <tr className="bg-blue-800 text-white">
                                <th className="text-left px-4 py-3 text-sm font-semibold">Опис</th>
                                <th className="text-right px-4 py-3 text-sm font-semibold">Кол.</th>
                                <th className="text-right px-4 py-3 text-sm font-semibold">Цена</th>
                                <th className="text-right px-4 py-3 text-sm font-semibold">ДДВ</th>
                                <th className="text-right px-4 py-3 text-sm font-semibold">Рабат</th>
                                <th className="text-right px-4 py-3 text-sm font-semibold">Вкупно</th>
                            </tr>
                        </thead>
                        <tbody>
                            {document.items.map((item, index) => {
                                const subtotal = item.quantity * item.unit_price;
                                const discountAmount = subtotal * ((item.discount || 0) / 100);
                                const afterDiscount = Math.round((subtotal - discountAmount) * 100) / 100;
                                const tax = Math.round(afterDiscount * (item.tax_rate / 100) * 100) / 100;
                                const total = afterDiscount + tax;
                                return (
                                    <tr key={index} className={index % 2 === 1 ? 'bg-gray-50' : 'bg-white'}>
                                        <td className="px-4 py-3 text-sm border-b border-gray-200">{item.description}</td>
                                        <td className="px-4 py-3 text-sm text-right border-b border-gray-200">{formatNumber(item.quantity, 2)}</td>
                                        <td className="px-4 py-3 text-sm text-right border-b border-gray-200">{formatNumber(item.unit_price, 2)}</td>
                                        <td className="px-4 py-3 text-sm text-right border-b border-gray-200">{item.tax_rate}%</td>
                                        <td className="px-4 py-3 text-sm text-right border-b border-gray-200">{Number(item.discount || 0).toFixed(0)}%</td>
                                        <td className="px-4 py-3 text-sm text-right font-medium border-b border-gray-200">{formatNumber(total, 2)}</td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            )}

            {/* Totals - only show if has items */}
            {hasItems && (
                <div className="flex justify-end mb-8">
                    <div className="w-64">
                        {(() => {
                            const grossSubtotal = document.items?.reduce((sum: number, item: any) => sum + Number(item.quantity) * Number(item.unit_price), 0) || 0;
                            const totalDiscountAmount = grossSubtotal - Number(document.subtotal);
                            return (
                                <>
                                    <div className="flex justify-between text-sm text-gray-500 py-1">
                                        <span>Меѓузбир:</span><span>{formatNumber(grossSubtotal, 2)} {currencySymbol}</span>
                                    </div>
                                    {totalDiscountAmount > 0 && (
                                        <div className="flex justify-between text-sm text-red-500 py-1">
                                            <span>Рабат:</span><span>-{formatNumber(totalDiscountAmount, 2)} {currencySymbol}</span>
                                        </div>
                                    )}
                                    <div className="flex justify-between text-sm text-gray-500 py-1">
                                        <span>ДДВ:</span><span>{formatNumber(document.tax_amount, 2)} {currencySymbol}</span>
                                    </div>
                                    <div className="flex justify-between text-lg font-bold text-blue-800 py-2 border-t-2 border-blue-800 mt-2">
                                        <span>ВКУПНО:</span><span>{formatNumber(document.total, getCurrencyDecimals(document.currency))} {currencySymbol}</span>
                                    </div>
                                </>
                            );
                        })()}
                    </div>
                </div>
            )}

            {/* Total for text-only offers */}
            {offer && !hasItems && document.total > 0 && (
                <div className="flex justify-end mb-8">
                    <div className="bg-blue-50 border border-blue-200 rounded-lg px-6 py-4">
                        <div className="text-sm text-blue-600 mb-1">Вкупна вредност</div>
                        <div className="text-2xl font-bold text-blue-800">{formatNumber(document.total, getCurrencyDecimals(document.currency))} {currencySymbol}</div>
                    </div>
                </div>
            )}

            {/* Bank Account */}
            {bankAccount && type !== 'offer' && (
                <div className="mb-6">
                    <h3 className="text-sm font-bold text-blue-800 mb-2 uppercase tracking-wide">Банкарска сметка</h3>
                    <div className="text-sm text-gray-600">
                        <div className="font-medium">{bankAccount.bank_name}</div>
                        <div>Сметка: {bankAccount.account_number}</div>
                        {bankAccount.iban && <div>IBAN: {bankAccount.iban}</div>}
                    </div>
                </div>
            )}

            {/* Notes & Footer */}
            {document.notes && (
                <div className="mt-6 pt-4 border-t border-gray-200">
                    <h3 className="text-sm font-bold text-blue-800 mb-2 uppercase">Забелешки</h3>
                    <p className="text-sm text-gray-600 whitespace-pre-wrap">{document.notes}</p>
                </div>
            )}
            {agency && (
                <div className="mt-8 pt-4 border-t border-gray-200 text-center text-xs text-gray-400">
                    {[agency.name, agency.website, agency.email].filter(Boolean).join(' | ')}
                </div>
            )}
        </div>
    );
}

// ============ MODERN TEMPLATE ============
function ModernTemplate({ document, type, agency, bankAccount }: Omit<InvoicePreviewProps, 'template'>) {
    const currencySymbol = getCurrencySymbol(document.currency);
    const offer = isOffer(document) ? document : null;
    const hasItems = offer ? offer.has_items : true;

    const getDocumentNumber = () => {
        if ('invoice_number' in document) return document.invoice_number;
        if ('proforma_number' in document) return document.proforma_number;
        if ('offer_number' in document) return document.offer_number;
        return '';
    };

    const getDueDate = () => {
        if ('due_date' in document) return document.due_date;
        if ('valid_until' in document) return document.valid_until;
        return null;
    };

    return (
        <div style={{ fontFamily: 'system-ui, -apple-system, sans-serif' }}>
            {/* Gradient Header */}
            <div className="bg-gradient-to-r from-violet-600 via-purple-600 to-indigo-600 text-white p-8 rounded-t-lg">
                <div className="flex justify-between items-start">
                    <div>
                        {agency?.name && <h2 className="text-2xl font-bold mb-1">{agency.name}</h2>}
                        <div className="text-sm text-white/80 space-y-0.5">
                            {agency?.address && <div>{agency.address}</div>}
                            {agency?.city && <div>{agency.city}</div>}
                            {agency?.email && <div>{agency.email}</div>}
                        </div>
                    </div>
                    <div className="text-right">
                        <div className="inline-block bg-white/20 backdrop-blur-sm rounded-xl px-6 py-4">
                            <h1 className="text-2xl font-bold">{titles[type]}</h1>
                            <div className="text-white/80 text-sm mt-1">{getDocumentNumber()}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="p-8 bg-gray-50">
                {/* Info Cards */}
                <div className="grid grid-cols-3 gap-4 -mt-12 mb-8">
                    <div className="bg-white rounded-xl shadow-lg p-5">
                        <div className="text-xs font-semibold text-purple-600 uppercase tracking-wider mb-2">Клиент</div>
                        <div className="font-bold text-gray-900">{document.client?.company || document.client?.name}</div>
                        <div className="text-sm text-gray-500 mt-1">
                            {document.client?.address && <div>{document.client.address}</div>}
                            {document.client?.city && <div>{document.client.city}</div>}
                        </div>
                    </div>
                    <div className="bg-white rounded-xl shadow-lg p-5">
                        <div className="text-xs font-semibold text-purple-600 uppercase tracking-wider mb-2">Датум</div>
                        <div className="font-bold text-gray-900">{formatDate(document.issue_date)}</div>
                        {getDueDate() && (
                            <div className="text-sm text-gray-500 mt-1">
                                {type === 'invoice' ? 'Доспева' : 'Важи до'}: {formatDate(getDueDate())}
                            </div>
                        )}
                    </div>
                    <div className="bg-white rounded-xl shadow-lg p-5">
                        <div className="text-xs font-semibold text-purple-600 uppercase tracking-wider mb-2">Износ</div>
                        <div className="font-bold text-2xl text-gray-900">{formatNumber(document.total, getCurrencyDecimals(document.currency))}</div>
                        <div className="text-sm text-gray-500">{currencySymbol}</div>
                    </div>
                </div>

                {/* Offer Title */}
                {offer?.title && (
                    <div className="mb-6">
                        <h2 className="text-xl font-semibold text-gray-900">{offer.title}</h2>
                    </div>
                )}

                {/* Offer Content (when no items) */}
                {offer && !hasItems && offer.content && (
                    <div className="bg-white rounded-xl shadow-lg p-6 mb-6">
                        <div className="text-xs font-semibold text-purple-600 uppercase tracking-wider mb-4">Опис на понудата</div>
                        <div
                            className="text-sm text-gray-700 leading-relaxed prose prose-sm max-w-none"
                            dangerouslySetInnerHTML={{ __html: offer.content }}
                        />
                    </div>
                )}

                {/* Items Table - only show if has items */}
                {hasItems && document.items && document.items.length > 0 && (
                    <div className="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                        <table className="w-full">
                            <thead>
                                <tr className="bg-gradient-to-r from-violet-500 to-purple-500 text-white">
                                    <th className="text-left px-6 py-4 text-sm font-semibold">Опис</th>
                                    <th className="text-right px-4 py-4 text-sm font-semibold">Кол.</th>
                                    <th className="text-right px-4 py-4 text-sm font-semibold">Цена</th>
                                    <th className="text-right px-4 py-4 text-sm font-semibold">ДДВ</th>
                                    <th className="text-right px-4 py-4 text-sm font-semibold">Рабат</th>
                                    <th className="text-right px-6 py-4 text-sm font-semibold">Вкупно</th>
                                </tr>
                            </thead>
                            <tbody>
                                {document.items.map((item, index) => {
                                    const subtotal = item.quantity * item.unit_price;
                                    const discountAmount = subtotal * ((item.discount || 0) / 100);
                                    const afterDiscount = subtotal - discountAmount;
                                    const tax = afterDiscount * (item.tax_rate / 100);
                                    const total = afterDiscount + tax;
                                    return (
                                        <tr key={index} className="border-b border-gray-100 hover:bg-purple-50/50 transition-colors">
                                            <td className="px-6 py-4 text-sm text-gray-900">{item.description}</td>
                                            <td className="px-4 py-4 text-sm text-right text-gray-600">{formatNumber(item.quantity, 2)}</td>
                                            <td className="px-4 py-4 text-sm text-right text-gray-600">{formatNumber(item.unit_price, 2)}</td>
                                            <td className="px-4 py-4 text-sm text-right text-gray-600">{item.tax_rate}%</td>
                                            <td className="px-4 py-4 text-sm text-right text-gray-600">{Number(item.discount || 0).toFixed(0)}%</td>
                                            <td className="px-6 py-4 text-sm text-right font-semibold text-gray-900">{formatNumber(total, 2)}</td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>

                        {/* Totals inside card */}
                        <div className="bg-gray-50 px-6 py-4">
                            <div className="flex justify-end">
                                <div className="w-64 space-y-2">
                                    {(() => {
                                        const grossSubtotal = document.items?.reduce((sum: number, item: any) => sum + Number(item.quantity) * Number(item.unit_price), 0) || 0;
                                        const totalDiscountAmount = grossSubtotal - Number(document.subtotal);
                                        return (
                                            <>
                                                <div className="flex justify-between text-sm text-gray-500">
                                                    <span>Меѓузбир</span><span>{formatNumber(grossSubtotal, 2)} {currencySymbol}</span>
                                                </div>
                                                {totalDiscountAmount > 0 && (
                                                    <div className="flex justify-between text-sm text-red-500">
                                                        <span>Рабат</span><span>-{formatNumber(totalDiscountAmount, 2)} {currencySymbol}</span>
                                                    </div>
                                                )}
                                                <div className="flex justify-between text-sm text-gray-500">
                                                    <span>ДДВ</span><span>{formatNumber(document.tax_amount, 2)} {currencySymbol}</span>
                                                </div>
                                                <div className="flex justify-between text-xl font-bold text-purple-600 pt-2 border-t border-purple-200">
                                                    <span>Вкупно</span><span>{formatNumber(document.total, getCurrencyDecimals(document.currency))} {currencySymbol}</span>
                                                </div>
                                            </>
                                        );
                                    })()}
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Bank & Notes */}
                <div className="grid grid-cols-2 gap-4">
                    {bankAccount && type !== 'offer' && (
                        <div className="bg-white rounded-xl shadow-lg p-5">
                            <div className="text-xs font-semibold text-purple-600 uppercase tracking-wider mb-3">Банкарска сметка</div>
                            <div className="text-sm text-gray-700 space-y-1">
                                <div className="font-semibold">{bankAccount.bank_name}</div>
                                <div>{bankAccount.account_number}</div>
                                {bankAccount.iban && <div className="text-gray-500">IBAN: {bankAccount.iban}</div>}
                            </div>
                        </div>
                    )}
                    {document.notes && (
                        <div className={`bg-white rounded-xl shadow-lg p-5 ${!bankAccount || type === 'offer' ? 'col-span-2' : ''}`}>
                            <div className="text-xs font-semibold text-purple-600 uppercase tracking-wider mb-3">Забелешки</div>
                            <p className="text-sm text-gray-600 whitespace-pre-wrap">{document.notes}</p>
                        </div>
                    )}
                </div>

                {/* Footer */}
                {agency && (
                    <div className="mt-8 text-center text-xs text-gray-400">
                        {[agency.name, agency.website, agency.email].filter(Boolean).join(' • ')}
                    </div>
                )}
            </div>
        </div>
    );
}

// ============ MINIMAL TEMPLATE ============
function MinimalTemplate({ document, type, agency, bankAccount }: Omit<InvoicePreviewProps, 'template'>) {
    const currencySymbol = getCurrencySymbol(document.currency);
    const offer = isOffer(document) ? document : null;
    const hasItems = offer ? offer.has_items : true;

    const getDocumentNumber = () => {
        if ('invoice_number' in document) return document.invoice_number;
        if ('proforma_number' in document) return document.proforma_number;
        if ('offer_number' in document) return document.offer_number;
        return '';
    };

    const getDueDate = () => {
        if ('due_date' in document) return document.due_date;
        if ('valid_until' in document) return document.valid_until;
        return null;
    };

    return (
        <div className="p-12 bg-white" style={{ fontFamily: 'Georgia, serif' }}>
            {/* Minimal Header */}
            <div className="flex justify-between items-start mb-16">
                <div>
                    {agency?.name && <h2 className="text-lg font-normal tracking-wide text-gray-900 mb-4">{agency.name}</h2>}
                    <div className="text-sm text-gray-400 space-y-1">
                        {agency?.address && <div>{agency.address}</div>}
                        {agency?.city && <div>{agency.city}</div>}
                        {agency?.tax_number && <div>ЕДБ {agency.tax_number}</div>}
                    </div>
                </div>
                <div className="text-right">
                    <h1 className="text-sm uppercase tracking-[0.3em] text-gray-400 mb-2">{titles[type]}</h1>
                    <div className="text-2xl font-light text-gray-900">{getDocumentNumber()}</div>
                </div>
            </div>

            {/* Offer Title */}
            {offer?.title && (
                <div className="mb-12">
                    <h2 className="text-xl font-normal text-gray-900">{offer.title}</h2>
                </div>
            )}

            {/* Two Column Info */}
            <div className="grid grid-cols-2 gap-16 mb-16">
                <div>
                    <div className="text-xs uppercase tracking-[0.2em] text-gray-400 mb-4">Клиент</div>
                    <div className="text-gray-900">{document.client?.company || document.client?.name}</div>
                    <div className="text-sm text-gray-400 mt-2 space-y-1">
                        {document.client?.address && <div>{document.client.address}</div>}
                        {document.client?.city && <div>{document.client.city}</div>}
                        {document.client?.tax_number && <div>ЕДБ {document.client.tax_number}</div>}
                    </div>
                </div>
                <div className="text-right">
                    <div className="text-xs uppercase tracking-[0.2em] text-gray-400 mb-4">Детали</div>
                    <div className="text-sm text-gray-600 space-y-2">
                        <div>Датум: <span className="text-gray-900">{formatDate(document.issue_date)}</span></div>
                        {getDueDate() && (
                            <div>{type === 'invoice' ? 'Доспева' : 'Важи до'}: <span className="text-gray-900">{formatDate(getDueDate())}</span></div>
                        )}
                    </div>
                </div>
            </div>

            {/* Offer Content (when no items) */}
            {offer && !hasItems && offer.content && (
                <div className="mb-12 pb-8 border-b border-gray-100">
                    <div className="text-xs uppercase tracking-[0.2em] text-gray-400 mb-4">Опис на понудата</div>
                    <div
                        className="text-gray-700 leading-relaxed prose prose-sm max-w-none"
                        dangerouslySetInnerHTML={{ __html: offer.content }}
                    />
                </div>
            )}

            {/* Minimal Items - only show if has items */}
            {hasItems && document.items && document.items.length > 0 && (
                <div className="mb-12">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b border-gray-200">
                                <th className="text-left py-4 text-xs uppercase tracking-[0.2em] text-gray-400 font-normal">Опис</th>
                                <th className="text-right py-4 text-xs uppercase tracking-[0.2em] text-gray-400 font-normal">Кол.</th>
                                <th className="text-right py-4 text-xs uppercase tracking-[0.2em] text-gray-400 font-normal">Цена</th>
                                <th className="text-right py-4 text-xs uppercase tracking-[0.2em] text-gray-400 font-normal">Рабат</th>
                                <th className="text-right py-4 text-xs uppercase tracking-[0.2em] text-gray-400 font-normal">Износ</th>
                            </tr>
                        </thead>
                        <tbody>
                            {document.items.map((item, index) => {
                                const subtotal = item.quantity * item.unit_price;
                                const discountAmount = subtotal * ((item.discount || 0) / 100);
                                const afterDiscount = Math.round((subtotal - discountAmount) * 100) / 100;
                                const tax = Math.round(afterDiscount * (item.tax_rate / 100) * 100) / 100;
                                const total = afterDiscount + tax;
                                return (
                                    <tr key={index} className="border-b border-gray-100">
                                        <td className="py-5 text-gray-900">{item.description}</td>
                                        <td className="py-5 text-right text-gray-600">{formatNumber(item.quantity, 0)}</td>
                                        <td className="py-5 text-right text-gray-600">{formatNumber(item.unit_price, 2)}</td>
                                        <td className="py-5 text-right text-gray-600">{Number(item.discount || 0).toFixed(0)}%</td>
                                        <td className="py-5 text-right text-gray-900">{formatNumber(total, 2)}</td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            )}

            {/* Minimal Totals - only show if has items */}
            {hasItems && (
                <div className="flex justify-end mb-16">
                    <div className="w-72">
                        {(() => {
                            const grossSubtotal = document.items?.reduce((sum: number, item: any) => sum + Number(item.quantity) * Number(item.unit_price), 0) || 0;
                            const totalDiscountAmount = grossSubtotal - Number(document.subtotal);
                            return (
                                <>
                                    <div className="flex justify-between py-2 text-sm text-gray-400">
                                        <span>Меѓузбир</span><span className="text-gray-600">{formatNumber(grossSubtotal, 2)}</span>
                                    </div>
                                    {totalDiscountAmount > 0 && (
                                        <div className="flex justify-between py-2 text-sm text-red-500">
                                            <span>Рабат</span><span>-{formatNumber(totalDiscountAmount, 2)}</span>
                                        </div>
                                    )}
                                    <div className="flex justify-between py-2 text-sm text-gray-400">
                                        <span>ДДВ</span><span className="text-gray-600">{formatNumber(document.tax_amount, 2)}</span>
                                    </div>
                                    <div className="flex justify-between py-4 border-t border-gray-900 mt-2">
                                        <span className="text-xs uppercase tracking-[0.2em] text-gray-400">Вкупно</span>
                                        <span className="text-2xl font-light text-gray-900">{formatNumber(document.total, getCurrencyDecimals(document.currency))} <span className="text-sm">{currencySymbol}</span></span>
                                    </div>
                                </>
                            );
                        })()}
                    </div>
                </div>
            )}

            {/* Total for text-only offers */}
            {offer && !hasItems && document.total > 0 && (
                <div className="flex justify-end mb-16">
                    <div className="border-t border-gray-900 pt-4">
                        <span className="text-xs uppercase tracking-[0.2em] text-gray-400 mr-8">Вкупна вредност</span>
                        <span className="text-2xl font-light text-gray-900">{formatNumber(document.total, getCurrencyDecimals(document.currency))} <span className="text-sm">{currencySymbol}</span></span>
                    </div>
                </div>
            )}

            {/* Bank & Notes - Minimal */}
            <div className="grid grid-cols-2 gap-16 pt-8 border-t border-gray-100">
                {bankAccount && type !== 'offer' && (
                    <div>
                        <div className="text-xs uppercase tracking-[0.2em] text-gray-400 mb-3">Плаќање</div>
                        <div className="text-sm text-gray-600 space-y-1">
                            <div>{bankAccount.bank_name}</div>
                            <div>{bankAccount.account_number}</div>
                            {bankAccount.iban && <div className="text-gray-400">{bankAccount.iban}</div>}
                        </div>
                    </div>
                )}
                {document.notes && (
                    <div className={!bankAccount || type === 'offer' ? 'col-span-2' : ''}>
                        <div className="text-xs uppercase tracking-[0.2em] text-gray-400 mb-3">Забелешки</div>
                        <p className="text-sm text-gray-600 whitespace-pre-wrap">{document.notes}</p>
                    </div>
                )}
            </div>

            {/* Footer */}
            {agency && (
                <div className="mt-16 pt-8 border-t border-gray-100 text-center text-xs text-gray-300 tracking-wide">
                    {agency.name}
                </div>
            )}
        </div>
    );
}

// ============ FORMAL TEMPLATE ============
function FormalTemplate({ document, type, agency, bankAccount }: Omit<InvoicePreviewProps, 'template'>) {
    const currencySymbol = getCurrencySymbol(document.currency);
    const offer = isOffer(document) ? document : null;
    const hasItems = offer ? offer.has_items : true;

    const getDocumentNumber = () => {
        if ('invoice_number' in document) return document.invoice_number;
        if ('proforma_number' in document) return document.proforma_number;
        if ('offer_number' in document) return document.offer_number;
        return '';
    };

    const getDueDate = () => {
        if ('due_date' in document) return document.due_date;
        if ('valid_until' in document) return document.valid_until;
        return null;
    };

    // Calculate tax breakdown
    let tax5Base = 0, tax18Base = 0, tax5Amount = 0, tax18Amount = 0;
    let totalBase = 0, totalWithVat = 0;

    const itemsData = (document.items || []).map((item: any) => {
        const lineSubtotal = item.quantity * item.unit_price;
        const lineDiscount = lineSubtotal * ((item.discount || 0) / 100);
        const lineBase = Math.round((lineSubtotal - lineDiscount) * 100) / 100;
        const lineTax = Math.round(lineBase * (item.tax_rate / 100) * 100) / 100;
        const lineTotal = lineBase + lineTax;

        totalBase += lineBase;
        totalWithVat += lineTotal;

        if (item.tax_rate === 5) {
            tax5Base += lineBase;
            tax5Amount += lineTax;
        } else {
            tax18Base += lineBase;
            tax18Amount += lineTax;
        }

        return { ...item, base: lineBase, total: lineTotal };
    });

    const totalTax = tax5Amount + tax18Amount;

    return (
        <div className="p-8 text-[11px] leading-relaxed" style={{ fontFamily: 'system-ui, -apple-system, sans-serif' }}>
            {/* Header: Company info top-right */}
            <div className="flex justify-between items-start mb-6">
                <div className="w-1/3">
                    {agency?.logo && (
                        <img src={`/storage/${agency.logo}`} alt="Logo" className="mb-2" style={{ maxHeight: '100px', maxWidth: '180px' }} />
                    )}
                </div>
                <div className="text-right text-[10px] leading-snug">
                    {agency && (
                        <>
                            <div className="font-bold text-[12px] mb-1">{agency.name}</div>
                            {agency.address && <span>{agency.address} {agency.city || ''}</span>}
                            {agency.phone && <span> Телефон: {agency.phone}</span>}
                            {agency.email && <><br />email: {agency.email}</>}
                            {agency.tax_number && <><br />ЕДБ:{agency.tax_number}</>}
                            {bankAccount && <><br />Жиро сметка: {bankAccount.account_number} {bankAccount.bank_name}</>}
                        </>
                    )}
                </div>
            </div>

            {/* Document title and client */}
            <div className="flex justify-between items-start mb-6">
                <div>
                    <div className="font-bold mb-1">До</div>
                    <div className="font-bold">{document.client?.company || document.client?.name}</div>
                    {document.client?.address && <div>{document.client.address}</div>}
                    {document.client?.city && <div>{document.client.postal_code && `${document.client.postal_code} `}{document.client.city}</div>}
                </div>
                <div className="text-right">
                    <div className="text-[22px] font-bold mb-2">{titles[type]}</div>
                    <table className="ml-auto text-[11px]">
                        <tbody>
                            <tr><td className="pr-4 font-bold">Сериски број</td><td className="font-bold">{getDocumentNumber()}</td></tr>
                            <tr><td className="pr-4">Датум:</td><td className="font-bold">{formatDate(document.issue_date)}</td></tr>
                            <tr><td className="pr-4">Валута:</td><td className="font-bold">{getDueDate() ? formatDate(getDueDate()) : formatDate(document.issue_date)}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Items Table */}
            {hasItems && itemsData.length > 0 && (
                <>
                    <table className="w-full text-[10px] mb-1">
                        <thead>
                            <tr className="border-t border-b border-black">
                                <th className="text-left py-2 px-1 font-bold w-[4%]">Рб</th>
                                <th className="text-left py-2 px-1 font-bold w-[30%]">Опис на артикал - услуга</th>
                                <th className="text-right py-2 px-1 font-bold w-[8%]">Кол.</th>
                                <th className="text-right py-2 px-1 font-bold w-[12%]">Цена</th>
                                <th className="text-right py-2 px-1 font-bold w-[8%]">Рабат</th>
                                <th className="text-right py-2 px-1 font-bold w-[14%]">Износ</th>
                                <th className="text-center py-2 px-1 font-bold w-[8%]">ддв</th>
                                <th className="text-right py-2 px-1 font-bold w-[16%]">Вкупно</th>
                            </tr>
                        </thead>
                        <tbody>
                            {itemsData.map((item: any, index: number) => (
                                <tr key={index} className="border-b border-gray-300">
                                    <td className="py-2 px-1">{index + 1}.</td>
                                    <td className="py-2 px-1">{item.description}</td>
                                    <td className="py-2 px-1 text-right">{formatNumber(item.quantity, 0)}</td>
                                    <td className="py-2 px-1 text-right">{formatNumber(item.unit_price, 2)}</td>
                                    <td className="py-2 px-1 text-right">{Number(item.discount || 0).toFixed(0)}%</td>
                                    <td className="py-2 px-1 text-right">{formatNumber(item.base, 2)}</td>
                                    <td className="py-2 px-1 text-center">{item.tax_rate}%</td>
                                    <td className="py-2 px-1 text-right">{formatNumber(item.total, 2)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    {/* Closing row number */}
                    <div className="text-[10px] mb-4 mt-2 italic">
                        Заклучно со реден број {itemsData.length}.
                    </div>

                    {/* Total base */}
                    <div className="flex justify-end mb-3">
                        <div className="border-t border-black pt-2">
                            <span className="font-bold mr-6">Вкупна цена:</span>
                            <span className="font-bold">{formatNumber(totalBase, 2)}</span>
                        </div>
                    </div>

                    {/* Tax breakdown and payment */}
                    <div className="flex justify-between items-end mt-4">
                        <div className="text-[10px]">
                            <div className="mb-1"><span className="font-bold">Рок за плаќање:</span></div>
                            <div className="font-bold">
                                {getDueDate() && getDueDate() !== document.issue_date ? formatDate(getDueDate()) : 'Веднаш'}
                            </div>
                        </div>
                        <div>
                            <table className="text-[10px] border-collapse">
                                <tbody>
                                    <tr className="border-t border-b border-black">
                                        <td className="py-1 px-2">Осн. 5%:</td>
                                        <td className="py-1 px-2 text-right">{formatNumber(tax5Base, 2)}</td>
                                        <td className="py-1 px-2">Осн. 18%:</td>
                                        <td className="py-1 px-2 text-right">{formatNumber(tax18Base, 2)}</td>
                                        <td className="py-1 px-2 font-bold">Основа:</td>
                                        <td className="py-1 px-2 text-right font-bold">{formatNumber(totalBase, 2)}</td>
                                    </tr>
                                    <tr className="border-b border-black">
                                        <td className="py-1 px-2">ДДВ 5%:</td>
                                        <td className="py-1 px-2 text-right">{formatNumber(tax5Amount, 2)}</td>
                                        <td className="py-1 px-2">ДДВ 18%:</td>
                                        <td className="py-1 px-2 text-right">{formatNumber(tax18Amount, 2)}</td>
                                        <td className="py-1 px-2 font-bold">ДДВ:</td>
                                        <td className="py-1 px-2 text-right font-bold">{formatNumber(totalTax, 2)}</td>
                                    </tr>
                                    <tr>
                                        <td colSpan={4}></td>
                                        <td className="py-2 px-2 font-bold text-[11px]">Вкупно за наплата денари:</td>
                                        <td className="py-2 px-2 text-right font-bold text-[11px]">{formatNumber(totalWithVat, getCurrencyDecimals(document.currency))}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </>
            )}

            {/* Notes */}
            {document.notes && (
                <div className="mt-4 mb-4 text-[10px]">
                    <span className="font-bold">Забелешка:</span> {document.notes}
                </div>
            )}

            {/* Signatures */}
            <div className="flex justify-between mt-16 pt-4 text-[10px]">
                <div className="text-center">
                    <div className="border-t border-black w-40 mb-1" />
                    <div>Примил</div>
                </div>
                <div className="text-center">
                    <div className="border-t border-black w-40 mb-1" />
                    <div>Фактурирал</div>
                </div>
                <div className="text-center">
                    <div className="border-t border-black w-40 mb-1" />
                    <div>Овластено лице</div>
                </div>
            </div>
        </div>
    );
}

// ============ MAIN COMPONENT ============
export default function InvoicePreview({ document, type, agency, bankAccount, template = 'classic' }: InvoicePreviewProps) {
    const headerColors: Record<string, string> = {
        classic: 'from-blue-600 to-blue-800',
        modern: 'from-violet-600 to-purple-600',
        minimal: 'from-gray-700 to-gray-900',
        formal: 'from-gray-800 to-gray-950',
    };

    return (
        <div className="bg-white shadow-lg rounded-lg overflow-hidden max-w-4xl mx-auto">
            {/* Preview Header Bar */}
            <div className={`bg-gradient-to-r ${headerColors[template]} text-white px-6 py-3 flex items-center justify-between`}>
                <span className="text-sm font-medium">Преглед на документ</span>
                <span className="text-xs opacity-75">{templateNames[template]}</span>
            </div>

            {/* Template Content */}
            {template === 'classic' && <ClassicTemplate document={document} type={type} agency={agency} bankAccount={bankAccount} />}
            {template === 'modern' && <ModernTemplate document={document} type={type} agency={agency} bankAccount={bankAccount} />}
            {template === 'minimal' && <MinimalTemplate document={document} type={type} agency={agency} bankAccount={bankAccount} />}
            {template === 'formal' && <FormalTemplate document={document} type={type} agency={agency} bankAccount={bankAccount} />}
        </div>
    );
}

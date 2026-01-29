import { FormEventHandler, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import {
    Dialog,
    DialogContent,
} from '@/Components/ui/dialog';
import { useTranslation } from '@/hooks/use-translation';
import { Check, Eye, FileText, FileSpreadsheet } from 'lucide-react';
import InvoicePreview from '@/Components/InvoicePreview';
import type { Template, Agency } from '@/types';

interface TemplatesPageProps {
    templates: Record<string, Template>;
    currentInvoiceTemplate: string;
    currentProformaTemplate: string;
    currentOfferTemplate: string;
    agency?: Agency;
}

// Sample data for preview
const sampleInvoice = {
    invoice_number: 'ФА-2025/001',
    issue_date: new Date().toISOString().split('T')[0],
    due_date: new Date(Date.now() + 15 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    currency: 'MKD',
    status: 'draft',
    subtotal: 50000,
    tax_amount: 9000,
    total: 59000,
    notes: 'Ви благодариме за соработката!',
    client: {
        name: 'Примерна Компанија ДООЕЛ',
        company: 'Примерна Компанија ДООЕЛ',
        address: 'ул. Примерна бр. 123',
        city: 'Скопје',
        postal_code: '1000',
        email: 'info@primer.mk',
        tax_number: 'MK1234567890',
    },
    items: [
        { description: 'Веб дизајн услуги', quantity: 1, unit_price: 30000, tax_rate: 18 },
        { description: 'Хостинг (годишен)', quantity: 1, unit_price: 12000, tax_rate: 18 },
        { description: 'Одржување на веб страна', quantity: 2, unit_price: 4000, tax_rate: 18 },
    ],
};

const sampleOffer = {
    offer_number: 'ПОН-2025/001',
    title: 'Понуда за веб развој',
    issue_date: new Date().toISOString().split('T')[0],
    valid_until: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    currency: 'MKD',
    status: 'sent',
    subtotal: 80000,
    tax_amount: 14400,
    total: 94400,
    notes: 'Понудата важи 30 дена.',
    has_items: true,
    client: {
        name: 'Примерна Компанија ДООЕЛ',
        company: 'Примерна Компанија ДООЕЛ',
        address: 'ул. Примерна бр. 123',
        city: 'Скопје',
        postal_code: '1000',
        email: 'info@primer.mk',
        tax_number: 'MK1234567890',
    },
    items: [
        { description: 'Дизајн на веб страна', quantity: 1, unit_price: 45000, tax_rate: 18 },
        { description: 'Развој на функционалности', quantity: 1, unit_price: 35000, tax_rate: 18 },
    ],
};

export default function TemplatesPage({
    templates,
    currentInvoiceTemplate,
    currentOfferTemplate,
    agency,
}: TemplatesPageProps) {
    const { t } = useTranslation();
    const [previewOpen, setPreviewOpen] = useState(false);
    const [previewType, setPreviewType] = useState<'invoice' | 'offer'>('invoice');
    const [previewTemplate, setPreviewTemplate] = useState<'classic' | 'modern' | 'minimal'>('classic');

    const { data, setData, put, processing } = useForm({
        invoice_template: currentInvoiceTemplate,
        proforma_template: currentInvoiceTemplate, // Same as invoice
        offer_template: currentOfferTemplate,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put('/settings/templates');
    };

    const templateOptions = Object.entries(templates).map(([key, value]) => ({
        value: key,
        label: value.name,
        description: value.description,
    }));

    const handlePreview = (type: 'invoice' | 'offer', templateKey: 'classic' | 'modern' | 'minimal') => {
        setPreviewType(type);
        setPreviewTemplate(templateKey);
        setPreviewOpen(true);
    };

    const selectInvoiceTemplate = (templateKey: string) => {
        setData(prev => ({
            ...prev,
            invoice_template: templateKey,
            proforma_template: templateKey, // Keep in sync
        }));
    };

    const selectOfferTemplate = (templateKey: string) => {
        setData('offer_template', templateKey);
    };

    // Sample agency for preview
    const sampleAgency = agency || {
        name: 'Моја Агенција',
        address: 'ул. Центар бр. 1',
        city: 'Скопје',
        postal_code: '1000',
        phone: '+389 2 123 456',
        email: 'info@agencija.mk',
        tax_number: 'MK9876543210',
        registration_number: '1234567',
    };

    const sampleBankAccount = {
        bank_name: 'Стопанска Банка АД Скопје',
        account_number: '200-1234567890-12',
        iban: 'MK07200123456789012',
        swift: 'STOBMK2X',
        currency: 'MKD',
        is_default: true,
    };

    // Mini preview thumbnails for each template style
    const ClassicMiniPreview = ({ accent }: { accent: string }) => (
        <div className="w-[85%] h-[90%] bg-white rounded shadow-sm p-2 text-[6px] overflow-hidden">
            <div className="flex justify-between mb-2">
                <div className={`w-16 h-3 ${accent} rounded-sm`}></div>
                <div className={`w-12 h-2 ${accent} rounded-sm`}></div>
            </div>
            <div className={`h-px ${accent} mb-2`}></div>
            <div className="flex gap-2 mb-2">
                <div className="flex-1">
                    <div className={`w-8 h-1.5 ${accent} rounded-sm mb-1`}></div>
                    <div className="w-full h-1 bg-gray-200 rounded-sm mb-0.5"></div>
                    <div className="w-3/4 h-1 bg-gray-200 rounded-sm"></div>
                </div>
                <div className="flex-1">
                    <div className={`w-8 h-1.5 ${accent} rounded-sm mb-1`}></div>
                    <div className="w-full h-1 bg-gray-200 rounded-sm mb-0.5"></div>
                </div>
            </div>
            <div className={`${accent} h-2 rounded-sm mb-1`}></div>
            <div className="space-y-0.5">
                <div className="h-1.5 bg-gray-100 rounded-sm"></div>
                <div className="h-1.5 bg-white rounded-sm"></div>
                <div className="h-1.5 bg-gray-100 rounded-sm"></div>
            </div>
            <div className="mt-2 flex justify-end">
                <div className={`w-12 h-2 ${accent} rounded-sm`}></div>
            </div>
        </div>
    );

    const ModernMiniPreview = () => (
        <div className="w-[85%] h-[90%] bg-white rounded shadow-sm overflow-hidden">
            {/* Gradient header */}
            <div className="h-8 bg-gradient-to-r from-violet-500 via-purple-500 to-indigo-500 p-1.5">
                <div className="flex justify-between items-start">
                    <div className="w-10 h-2 bg-white/40 rounded-sm"></div>
                    <div className="w-8 h-4 bg-white/20 rounded"></div>
                </div>
            </div>
            {/* Cards */}
            <div className="p-1.5 bg-gray-100">
                <div className="flex gap-1 -mt-2 mb-1.5">
                    <div className="flex-1 bg-white rounded shadow-sm p-1 h-5"></div>
                    <div className="flex-1 bg-white rounded shadow-sm p-1 h-5"></div>
                    <div className="flex-1 bg-white rounded shadow-sm p-1 h-5"></div>
                </div>
                {/* Table card */}
                <div className="bg-white rounded shadow-sm overflow-hidden">
                    <div className="h-2 bg-gradient-to-r from-violet-400 to-purple-400"></div>
                    <div className="p-1 space-y-0.5">
                        <div className="h-1 bg-gray-100 rounded-sm"></div>
                        <div className="h-1 bg-white rounded-sm"></div>
                        <div className="h-1 bg-gray-100 rounded-sm"></div>
                    </div>
                    <div className="bg-gray-50 p-1 flex justify-end">
                        <div className="w-10 h-1.5 bg-purple-500 rounded-sm"></div>
                    </div>
                </div>
            </div>
        </div>
    );

    const MinimalMiniPreview = () => (
        <div className="w-[85%] h-[90%] bg-white rounded shadow-sm p-3 overflow-hidden" style={{ fontFamily: 'serif' }}>
            {/* Minimal header */}
            <div className="flex justify-between mb-3">
                <div>
                    <div className="w-12 h-1.5 bg-gray-300 rounded-sm mb-1"></div>
                    <div className="w-8 h-1 bg-gray-200 rounded-sm"></div>
                </div>
                <div className="text-right">
                    <div className="w-10 h-1 bg-gray-200 rounded-sm mb-1 ml-auto"></div>
                    <div className="w-14 h-2 bg-gray-400 rounded-sm"></div>
                </div>
            </div>
            {/* Thin line items */}
            <div className="border-t border-gray-200 pt-2 mb-2">
                <div className="space-y-1.5">
                    <div className="flex justify-between">
                        <div className="w-20 h-1 bg-gray-200 rounded-sm"></div>
                        <div className="w-8 h-1 bg-gray-300 rounded-sm"></div>
                    </div>
                    <div className="flex justify-between">
                        <div className="w-16 h-1 bg-gray-200 rounded-sm"></div>
                        <div className="w-8 h-1 bg-gray-300 rounded-sm"></div>
                    </div>
                    <div className="flex justify-between">
                        <div className="w-24 h-1 bg-gray-200 rounded-sm"></div>
                        <div className="w-8 h-1 bg-gray-300 rounded-sm"></div>
                    </div>
                </div>
            </div>
            {/* Total with top border */}
            <div className="flex justify-end pt-2 border-t border-gray-900">
                <div className="w-12 h-2 bg-gray-700 rounded-sm"></div>
            </div>
        </div>
    );

    const TemplateCard = ({
        template,
        isSelected,
        onSelect,
        onPreview,
        color = 'blue'
    }: {
        template: { value: string; label: string; description: string };
        isSelected: boolean;
        onSelect: () => void;
        onPreview: () => void;
        color?: 'blue' | 'orange';
    }) => {
        const colorClasses = {
            blue: {
                selected: 'border-blue-500 bg-blue-50/50 ring-2 ring-blue-200',
                badge: 'bg-blue-500',
                accent: 'bg-blue-800',
            },
            orange: {
                selected: 'border-orange-500 bg-orange-50/50 ring-2 ring-orange-200',
                badge: 'bg-orange-500',
                accent: 'bg-orange-600',
            },
        };

        const colors = colorClasses[color];

        const renderMiniPreview = () => {
            switch (template.value) {
                case 'modern':
                    return <ModernMiniPreview />;
                case 'minimal':
                    return <MinimalMiniPreview />;
                default:
                    return <ClassicMiniPreview accent={colors.accent} />;
            }
        };

        return (
            <div
                className={`relative p-4 border-2 rounded-xl transition-all cursor-pointer hover:shadow-md ${
                    isSelected
                        ? colors.selected
                        : 'border-gray-200 hover:border-gray-300'
                }`}
                onClick={onSelect}
            >
                {/* Selected Badge */}
                {isSelected && (
                    <div className={`absolute -top-2 -right-2 ${colors.badge} text-white rounded-full p-1`}>
                        <Check className="w-4 h-4" />
                    </div>
                )}

                {/* Template Preview Thumbnail */}
                <div className="w-full h-40 bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg mb-3 flex items-center justify-center border border-gray-200 overflow-hidden">
                    {renderMiniPreview()}
                </div>

                {/* Template Info */}
                <div className="flex items-center justify-between">
                    <div>
                        <h4 className="font-semibold text-gray-900">{template.label}</h4>
                        <p className="text-xs text-gray-500 mt-0.5">{template.description}</p>
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="shrink-0"
                        onClick={(e) => {
                            e.stopPropagation();
                            onPreview();
                        }}
                    >
                        <Eye className="w-4 h-4 mr-1" />
                        {t('settings.preview')}
                    </Button>
                </div>
            </div>
        );
    };

    return (
        <AppLayout>
            <Head title={t('settings.templates_title')} />

            <div>
                {/* Header */}
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-900">{t('settings.templates_title')}</h1>
                    <p className="mt-1 text-sm text-gray-500">{t('settings.templates_desc')}</p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    {/* Invoice & Proforma Templates */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <FileText className="w-5 h-5 text-blue-600" />
                                </div>
                                <div>
                                    <CardTitle>{t('settings.invoice_proforma_template')}</CardTitle>
                                    <CardDescription>{t('settings.invoice_proforma_template_desc')}</CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                {templateOptions.map((template) => (
                                    <TemplateCard
                                        key={template.value}
                                        template={template}
                                        isSelected={data.invoice_template === template.value}
                                        onSelect={() => selectInvoiceTemplate(template.value)}
                                        onPreview={() => handlePreview('invoice', template.value as 'classic' | 'modern' | 'minimal')}
                                        color="blue"
                                    />
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Offer Templates */}
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                    <FileSpreadsheet className="w-5 h-5 text-orange-600" />
                                </div>
                                <div>
                                    <CardTitle>{t('settings.offer_template')}</CardTitle>
                                    <CardDescription>{t('settings.offer_template_desc')}</CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                {templateOptions.map((template) => (
                                    <TemplateCard
                                        key={template.value}
                                        template={template}
                                        isSelected={data.offer_template === template.value}
                                        onSelect={() => selectOfferTemplate(template.value)}
                                        onPreview={() => handlePreview('offer', template.value as 'classic' | 'modern' | 'minimal')}
                                        color="orange"
                                    />
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Save Button */}
                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing} loading={processing} size="lg">
                            {t('settings.save_templates')}
                        </Button>
                    </div>
                </form>

                {/* Preview Dialog */}
                <Dialog open={previewOpen} onOpenChange={setPreviewOpen}>
                    <DialogContent className="max-w-5xl max-h-[90vh] overflow-y-auto p-0">
                        <InvoicePreview
                            document={(previewType === 'invoice' ? sampleInvoice : sampleOffer) as any}
                            type={previewType}
                            agency={sampleAgency as any}
                            bankAccount={previewType === 'invoice' ? sampleBankAccount as any : undefined}
                            template={previewTemplate}
                        />
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}

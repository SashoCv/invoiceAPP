import { FormEventHandler } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { Button } from '@/Components/ui/button';
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
import { Check, FileText } from 'lucide-react';
import type { Template } from '@/types';

interface TemplatesPageProps {
    templates: Record<string, Template>;
    currentInvoiceTemplate: string;
    currentProformaTemplate: string;
    currentOfferTemplate: string;
}

export default function TemplatesPage({
    templates,
    currentInvoiceTemplate,
    currentProformaTemplate,
    currentOfferTemplate,
}: TemplatesPageProps) {
    const { t } = useTranslation();

    const { data, setData, put, processing } = useForm({
        invoice_template: currentInvoiceTemplate,
        proforma_template: currentProformaTemplate,
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

    return (
        <AppLayout>
            <Head title={t('settings.templates_title')} />

            <div>
                {/* Header */}
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-900">{t('settings.templates_title')}</h1>
                    <p className="mt-1 text-sm text-gray-500">{t('settings.templates_desc')}</p>
                </div>

                <form onSubmit={submit}>
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('settings.templates_title')}</CardTitle>
                            <CardDescription>{t('settings.templates_desc')}</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {/* Template Preview Cards */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                {templateOptions.map((template) => (
                                    <div
                                        key={template.value}
                                        className="p-4 border rounded-lg text-center"
                                    >
                                        <div className="w-full h-32 bg-gray-100 rounded-md mb-3 flex items-center justify-center">
                                            <FileText className="w-12 h-12 text-gray-400" />
                                        </div>
                                        <h4 className="font-medium">{template.label}</h4>
                                        <p className="text-xs text-gray-500 mt-1">{template.description}</p>
                                    </div>
                                ))}
                            </div>

                            {/* Invoice Template */}
                            <div>
                                <Label>{t('settings.invoice_template')}</Label>
                                <Select
                                    value={data.invoice_template}
                                    onValueChange={(v) => setData('invoice_template', v)}
                                >
                                    <SelectTrigger className="mt-1 max-w-md">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {templateOptions.map((template) => (
                                            <SelectItem key={template.value} value={template.value}>
                                                <div className="flex items-center gap-2">
                                                    {data.invoice_template === template.value && (
                                                        <Check className="w-4 h-4 text-green-500" />
                                                    )}
                                                    <span>{template.label}</span>
                                                </div>
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Proforma Template */}
                            <div>
                                <Label>{t('settings.proforma_template')}</Label>
                                <Select
                                    value={data.proforma_template}
                                    onValueChange={(v) => setData('proforma_template', v)}
                                >
                                    <SelectTrigger className="mt-1 max-w-md">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {templateOptions.map((template) => (
                                            <SelectItem key={template.value} value={template.value}>
                                                <div className="flex items-center gap-2">
                                                    {data.proforma_template === template.value && (
                                                        <Check className="w-4 h-4 text-green-500" />
                                                    )}
                                                    <span>{template.label}</span>
                                                </div>
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Offer Template */}
                            <div>
                                <Label>{t('settings.offer_template')}</Label>
                                <Select
                                    value={data.offer_template}
                                    onValueChange={(v) => setData('offer_template', v)}
                                >
                                    <SelectTrigger className="mt-1 max-w-md">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {templateOptions.map((template) => (
                                            <SelectItem key={template.value} value={template.value}>
                                                <div className="flex items-center gap-2">
                                                    {data.offer_template === template.value && (
                                                        <Check className="w-4 h-4 text-green-500" />
                                                    )}
                                                    <span>{template.label}</span>
                                                </div>
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="pt-4">
                                <Button type="submit" disabled={processing} loading={processing}>
                                    {t('settings.save_templates')}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </AppLayout>
    );
}

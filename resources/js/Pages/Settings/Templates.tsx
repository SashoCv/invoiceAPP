import { FormEventHandler, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import SettingsLayout from '@/Components/SettingsLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import {
    Dialog,
    DialogContent,
} from '@/Components/ui/dialog';
import { useTranslation } from '@/hooks/use-translation';
import { Check, Eye, FileText, FileSpreadsheet } from 'lucide-react';
import type { Template, Agency } from '@/types';

interface TemplatesPageProps {
    templates: Record<string, Template>;
    currentInvoiceTemplate: string;
    currentProformaTemplate: string;
    currentOfferTemplate: string;
    agency?: Agency;
}

export default function TemplatesPage({
    templates,
    currentInvoiceTemplate,
    currentOfferTemplate,
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
        <SettingsLayout>
            <Head title={t('settings.templates_title')} />

            <div>
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
                    <DialogContent className="max-w-5xl max-h-[90vh] overflow-hidden p-0">
                        <iframe
                            src={`/settings/templates/preview?template=${previewTemplate}&type=${previewType}`}
                            className="w-full h-[85vh] border-0"
                            title={`Preview ${previewTemplate}`}
                        />
                    </DialogContent>
                </Dialog>
            </div>
        </SettingsLayout>
    );
}

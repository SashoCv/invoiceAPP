import { FormEventHandler, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { useTranslation } from '@/hooks/use-translation';
import { Building2, Upload, X } from 'lucide-react';
import type { Agency } from '@/types';

interface AgencyPageProps {
    agency: Agency | null;
}

export default function AgencyPage({ agency }: AgencyPageProps) {
    const { t } = useTranslation();
    const [logoPreview, setLogoPreview] = useState<string | null>(null);

    const { data, setData, post, processing, errors } = useForm({
        name: agency?.name || '',
        address: agency?.address || '',
        city: agency?.city || '',
        postal_code: agency?.zip_code || '',
        country: agency?.country || '',
        phone: agency?.phone || '',
        email: agency?.email || '',
        website: agency?.website || '',
        tax_number: agency?.tax_number || '',
        registration_number: agency?.bank_account || '',
        logo: null as File | null,
        remove_logo: false,
        _method: 'PUT',
    });

    const handleLogoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setData('logo', file);
            setLogoPreview(URL.createObjectURL(file));
        }
    };

    const removeLogo = () => {
        setData('remove_logo', true);
        setData('logo', null);
        setLogoPreview(null);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/settings/agency', {
            forceFormData: true,
        });
    };

    return (
        <AppLayout>
            <Head title={t('settings.agency_title')} />

            <div>
                {/* Header */}
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-900">{t('settings.agency_info')}</h1>
                    <p className="mt-1 text-sm text-gray-500">{t('settings.agency_info_desc')}</p>
                </div>

                <form onSubmit={submit}>
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Building2 className="w-5 h-5" />
                                {t('settings.agency_info')}
                            </CardTitle>
                            <CardDescription>{t('settings.agency_info_desc')}</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {/* Logo */}
                            <div>
                                <Label>{t('settings.logo')}</Label>
                                <div className="mt-2 flex items-center gap-4">
                                    {(logoPreview || agency?.logo) && (
                                        <div className="relative">
                                            <img
                                                src={logoPreview || `/storage/${agency?.logo}`}
                                                alt="Logo"
                                                className="h-20 w-auto object-contain border rounded"
                                            />
                                            <button
                                                type="button"
                                                onClick={removeLogo}
                                                className="absolute -top-2 -right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600"
                                            >
                                                <X className="w-3 h-3" />
                                            </button>
                                        </div>
                                    )}
                                    <label className="flex items-center gap-2 px-4 py-2 border rounded-md cursor-pointer hover:bg-gray-50">
                                        <Upload className="w-4 h-4" />
                                        <span className="text-sm">{t('settings.upload_logo')}</span>
                                        <input
                                            type="file"
                                            accept="image/*"
                                            onChange={handleLogoChange}
                                            className="hidden"
                                        />
                                    </label>
                                </div>
                                <p className="mt-1 text-xs text-gray-500">{t('settings.logo_hint')}</p>
                            </div>

                            {/* Company Name */}
                            <div>
                                <Label htmlFor="name">{t('settings.company_name')} *</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="mt-1"
                                    error={errors.name}
                                />
                            </div>

                            {/* Address */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="address">{t('settings.address')}</Label>
                                    <Input
                                        id="address"
                                        value={data.address}
                                        onChange={(e) => setData('address', e.target.value)}
                                        className="mt-1"
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="city">{t('settings.city')}</Label>
                                    <Input
                                        id="city"
                                        value={data.city}
                                        onChange={(e) => setData('city', e.target.value)}
                                        className="mt-1"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="postal_code">{t('settings.postal_code')}</Label>
                                    <Input
                                        id="postal_code"
                                        value={data.postal_code}
                                        onChange={(e) => setData('postal_code', e.target.value)}
                                        className="mt-1"
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="country">{t('settings.country')}</Label>
                                    <Input
                                        id="country"
                                        value={data.country}
                                        onChange={(e) => setData('country', e.target.value)}
                                        className="mt-1"
                                    />
                                </div>
                            </div>

                            {/* Contact */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="phone">{t('settings.phone')}</Label>
                                    <Input
                                        id="phone"
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                        className="mt-1"
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="email">{t('settings.company_email')}</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        className="mt-1"
                                    />
                                </div>
                            </div>

                            <div>
                                <Label htmlFor="website">{t('settings.website')}</Label>
                                <Input
                                    id="website"
                                    value={data.website}
                                    onChange={(e) => setData('website', e.target.value)}
                                    className="mt-1"
                                    placeholder="https://"
                                />
                            </div>

                            {/* Tax Info */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="tax_number">{t('settings.tax_number')}</Label>
                                    <Input
                                        id="tax_number"
                                        value={data.tax_number}
                                        onChange={(e) => setData('tax_number', e.target.value)}
                                        className="mt-1"
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="registration_number">{t('settings.registration_number')}</Label>
                                    <Input
                                        id="registration_number"
                                        value={data.registration_number}
                                        onChange={(e) => setData('registration_number', e.target.value)}
                                        className="mt-1"
                                    />
                                </div>
                            </div>

                            <div className="pt-4">
                                <Button type="submit" disabled={processing} loading={processing}>
                                    {t('settings.save_agency')}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </AppLayout>
    );
}

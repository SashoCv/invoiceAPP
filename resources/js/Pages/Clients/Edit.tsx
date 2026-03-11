import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { useTranslation } from '@/hooks/use-translation';
import { ArrowLeft } from 'lucide-react';
import type { Client, ClientContract } from '@/types';

interface ClientEditProps {
    client: Client;
    contracts: ClientContract[];
}

export default function ClientEdit({ client, contracts }: ClientEditProps) {
    const { t } = useTranslation();
    const { data, setData, put, processing, errors } = useForm({
        name: client.name || '',
        company: client.company || '',
        email: client.email || '',
        phone: client.phone || '',
        address: client.address || '',
        city: client.city || '',
        postal_code: client.postal_code || '',
        country: client.country || 'Македонија',
        tax_number: client.tax_number || '',
        registration_number: client.registration_number || '',
        bank_name: client.bank_name || '',
        bank_account: client.bank_account || '',
        discount: client.discount ?? 0,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(`/clients/${client.id}`);
    };

    return (
        <AppLayout>
            <Head title={`${t('clients.edit_title')} - ${client.name}`} />

            <div className="max-w-2xl mx-auto">
                {/* Header */}
                <div className="mb-6">
                    <Button variant="ghost" size="sm" asChild className="mb-4">
                        <Link href="/clients" className="flex items-center gap-2">
                            <ArrowLeft className="w-4 h-4" />
                            {t('clients.back_to_list')}
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-bold text-gray-900">{t('clients.edit_title')}</h1>
                    <p className="mt-1 text-sm text-gray-500">{client.name}</p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('clients.client_info')}</CardTitle>
                        <CardDescription>{t('clients.client_info_description')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            {/* Company / Name */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="company">{t('clients.company')}</Label>
                                    <Input
                                        id="company"
                                        value={data.company}
                                        onChange={(e) => setData('company', e.target.value)}
                                        placeholder={t('clients.company_placeholder')}
                                        className="mt-1"
                                        error={errors.company}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="name">{t('clients.contact_person')} *</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        required
                                        className="mt-1"
                                        error={errors.name}
                                    />
                                </div>
                            </div>

                            {/* Tax / Registration Numbers */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="tax_number">{t('clients.tax_number')}</Label>
                                    <Input
                                        id="tax_number"
                                        value={data.tax_number}
                                        onChange={(e) => setData('tax_number', e.target.value)}
                                        placeholder={t('clients.tax_number_placeholder')}
                                        className="mt-1"
                                        error={errors.tax_number}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="registration_number">{t('clients.registration_number')}</Label>
                                    <Input
                                        id="registration_number"
                                        value={data.registration_number}
                                        onChange={(e) => setData('registration_number', e.target.value)}
                                        className="mt-1"
                                        error={errors.registration_number}
                                    />
                                </div>
                            </div>

                            {/* Bank Info */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="bank_name">{t('clients.bank_name')}</Label>
                                    <Input
                                        id="bank_name"
                                        value={data.bank_name}
                                        onChange={(e) => setData('bank_name', e.target.value)}
                                        className="mt-1"
                                        error={errors.bank_name}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="bank_account">{t('clients.bank_account')}</Label>
                                    <Input
                                        id="bank_account"
                                        value={data.bank_account}
                                        onChange={(e) => setData('bank_account', e.target.value)}
                                        className="mt-1"
                                        error={errors.bank_account}
                                    />
                                </div>
                            </div>

                            {/* Discount */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="discount">{t('clients.discount')}</Label>
                                    <Input
                                        id="discount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        value={data.discount}
                                        onChange={(e) => setData('discount', parseFloat(e.target.value) || 0)}
                                        placeholder={t('clients.discount_placeholder')}
                                        className="mt-1"
                                        error={errors.discount}
                                    />
                                </div>
                            </div>

                            {/* Contact Info */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="email">{t('clients.email')}</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        className="mt-1"
                                        error={errors.email}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="phone">{t('clients.phone')}</Label>
                                    <Input
                                        id="phone"
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                        className="mt-1"
                                        error={errors.phone}
                                    />
                                </div>
                            </div>

                            {/* Address */}
                            <div>
                                <Label htmlFor="address">{t('clients.address')}</Label>
                                <Input
                                    id="address"
                                    value={data.address}
                                    onChange={(e) => setData('address', e.target.value)}
                                    className="mt-1"
                                    error={errors.address}
                                />
                            </div>

                            {/* City / Postal / Country */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <Label htmlFor="city">{t('clients.city')}</Label>
                                    <Input
                                        id="city"
                                        value={data.city}
                                        onChange={(e) => setData('city', e.target.value)}
                                        className="mt-1"
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="postal_code">{t('clients.postal_code')}</Label>
                                    <Input
                                        id="postal_code"
                                        value={data.postal_code}
                                        onChange={(e) => setData('postal_code', e.target.value)}
                                        className="mt-1"
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="country">{t('clients.country')}</Label>
                                    <Input
                                        id="country"
                                        value={data.country}
                                        onChange={(e) => setData('country', e.target.value)}
                                        className="mt-1"
                                    />
                                </div>
                            </div>

                            {/* Actions */}
                            <div className="flex items-center justify-end gap-4 pt-4 border-t">
                                <Button variant="outline" asChild>
                                    <Link href="/clients">{t('general.cancel')}</Link>
                                </Button>
                                <Button type="submit" disabled={processing} loading={processing}>
                                    {t('clients.update_client')}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

import { FormEventHandler, useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import SettingsLayout from '@/Components/SettingsLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Checkbox } from '@/Components/ui/checkbox';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/Components/ui/dialog';
import { useTranslation } from '@/hooks/use-translation';
import { CreditCard, Plus, Trash2, Building2, UserIcon, Pencil } from 'lucide-react';
import ActionDropdown from '@/Components/ActionDropdown';
import type { BankAccount } from '@/types';

interface BankAccountsPageProps {
    bankAccounts: BankAccount[];
    hasAgency: boolean;
}

export default function BankAccountsPage({ bankAccounts, hasAgency }: BankAccountsPageProps) {
    const { t } = useTranslation();
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editDialogOpen, setEditDialogOpen] = useState(false);
    const [editingAccount, setEditingAccount] = useState<BankAccount | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        bank_name: '',
        account_number: '',
        iban: '',
        swift: '',
        currency: 'MKD',
        type: 'denar',
        is_default: false,
        owner_type: 'user',
    });

    const { data: editData, setData: setEditData, put, processing: editProcessing, errors: editErrors, reset: resetEdit } = useForm({
        bank_name: '',
        account_number: '',
        iban: '',
        swift: '',
        currency: 'MKD',
        type: 'denar',
        is_default: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/settings/bank-accounts', {
            onSuccess: () => {
                setDialogOpen(false);
                reset();
            },
        });
    };

    const handleEdit = (account: BankAccount) => {
        setEditingAccount(account);
        setEditData({
            bank_name: account.bank_name,
            account_number: account.account_number,
            iban: account.iban || '',
            swift: account.swift || '',
            currency: account.currency,
            type: account.type,
            is_default: account.is_default,
        });
        setEditDialogOpen(true);
    };

    const submitEdit: FormEventHandler = (e) => {
        e.preventDefault();
        if (!editingAccount) return;
        put(`/settings/bank-accounts/${editingAccount.id}`, {
            onSuccess: () => {
                setEditDialogOpen(false);
                setEditingAccount(null);
                resetEdit();
            },
        });
    };

    const handleDelete = (id: number) => {
        if (confirm(t('settings.delete_bank_account_confirm'))) {
            router.delete(`/settings/bank-accounts/${id}`);
        }
    };

    return (
        <SettingsLayout>
            <Head title={t('settings.bank_accounts_title')} />

            <div>
                {/* Header */}
                <div className="flex items-center justify-end mb-6">
                    <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                        <DialogTrigger asChild>
                            <Button className="flex items-center gap-2">
                                <Plus className="w-4 h-4" />
                                {t('settings.add_bank_account')}
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>{t('settings.add_bank_account')}</DialogTitle>
                            </DialogHeader>
                            <form onSubmit={submit} className="space-y-4">
                                <div>
                                    <Label htmlFor="bank_name">{t('settings.bank_name')} *</Label>
                                    <Input
                                        id="bank_name"
                                        value={data.bank_name}
                                        onChange={(e) => setData('bank_name', e.target.value)}
                                        className="mt-1"
                                        error={errors.bank_name}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="account_number">{t('settings.account_number')} *</Label>
                                    <Input
                                        id="account_number"
                                        value={data.account_number}
                                        onChange={(e) => setData('account_number', e.target.value)}
                                        className="mt-1"
                                        error={errors.account_number}
                                    />
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label htmlFor="iban">IBAN</Label>
                                        <Input
                                            id="iban"
                                            value={data.iban}
                                            onChange={(e) => setData('iban', e.target.value)}
                                            className="mt-1"
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="swift">SWIFT</Label>
                                        <Input
                                            id="swift"
                                            value={data.swift}
                                            onChange={(e) => setData('swift', e.target.value)}
                                            className="mt-1"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label>{t('settings.currency')} *</Label>
                                        <Select value={data.currency} onValueChange={(v) => setData('currency', v)}>
                                            <SelectTrigger className="mt-1">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="MKD">MKD</SelectItem>
                                                <SelectItem value="EUR">EUR</SelectItem>
                                                <SelectItem value="USD">USD</SelectItem>
                                                <SelectItem value="GBP">GBP</SelectItem>
                                                <SelectItem value="CHF">CHF</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <Label>{t('settings.account_type')} *</Label>
                                        <Select value={data.type} onValueChange={(v) => setData('type', v)}>
                                            <SelectTrigger className="mt-1">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="denar">{t('settings.type_denar')}</SelectItem>
                                                <SelectItem value="foreign">{t('settings.type_foreign')}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                <div>
                                    <Label>{t('settings.owner')} *</Label>
                                    <Select value={data.owner_type} onValueChange={(v) => setData('owner_type', v)}>
                                        <SelectTrigger className="mt-1">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="user">{t('settings.owner_user')}</SelectItem>
                                            {hasAgency && (
                                                <SelectItem value="agency">{t('settings.owner_agency')}</SelectItem>
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Checkbox
                                        id="is_default"
                                        checked={data.is_default}
                                        onCheckedChange={(checked) => setData('is_default', !!checked)}
                                    />
                                    <Label htmlFor="is_default" className="cursor-pointer">
                                        {t('settings.set_as_default')}
                                    </Label>
                                </div>

                                <div className="flex justify-end gap-2 pt-4">
                                    <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>
                                        {t('general.cancel')}
                                    </Button>
                                    <Button type="submit" disabled={processing} loading={processing}>
                                        {t('settings.save_bank_account')}
                                    </Button>
                                </div>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                {/* Edit Dialog */}
                <Dialog open={editDialogOpen} onOpenChange={setEditDialogOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>{t('settings.edit_bank_account')}</DialogTitle>
                        </DialogHeader>
                        <form onSubmit={submitEdit} className="space-y-4">
                            <div>
                                <Label htmlFor="edit_bank_name">{t('settings.bank_name')} *</Label>
                                <Input
                                    id="edit_bank_name"
                                    value={editData.bank_name}
                                    onChange={(e) => setEditData('bank_name', e.target.value)}
                                    className="mt-1"
                                    error={editErrors.bank_name}
                                />
                            </div>

                            <div>
                                <Label htmlFor="edit_account_number">{t('settings.account_number')} *</Label>
                                <Input
                                    id="edit_account_number"
                                    value={editData.account_number}
                                    onChange={(e) => setEditData('account_number', e.target.value)}
                                    className="mt-1"
                                    error={editErrors.account_number}
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="edit_iban">IBAN</Label>
                                    <Input
                                        id="edit_iban"
                                        value={editData.iban}
                                        onChange={(e) => setEditData('iban', e.target.value)}
                                        className="mt-1"
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="edit_swift">SWIFT</Label>
                                    <Input
                                        id="edit_swift"
                                        value={editData.swift}
                                        onChange={(e) => setEditData('swift', e.target.value)}
                                        className="mt-1"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label>{t('settings.currency')} *</Label>
                                    <Select value={editData.currency} onValueChange={(v) => setEditData('currency', v)}>
                                        <SelectTrigger className="mt-1">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="MKD">MKD</SelectItem>
                                            <SelectItem value="EUR">EUR</SelectItem>
                                            <SelectItem value="USD">USD</SelectItem>
                                            <SelectItem value="GBP">GBP</SelectItem>
                                            <SelectItem value="CHF">CHF</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label>{t('settings.account_type')} *</Label>
                                    <Select value={editData.type} onValueChange={(v) => setEditData('type', v)}>
                                        <SelectTrigger className="mt-1">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="denar">{t('settings.type_denar')}</SelectItem>
                                            <SelectItem value="foreign">{t('settings.type_foreign')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="edit_is_default"
                                    checked={editData.is_default}
                                    onCheckedChange={(checked) => setEditData('is_default', !!checked)}
                                />
                                <Label htmlFor="edit_is_default" className="cursor-pointer">
                                    {t('settings.set_as_default')}
                                </Label>
                            </div>

                            <div className="flex justify-end gap-2 pt-4">
                                <Button type="button" variant="outline" onClick={() => setEditDialogOpen(false)}>
                                    {t('general.cancel')}
                                </Button>
                                <Button type="submit" disabled={editProcessing} loading={editProcessing}>
                                    {t('settings.update_bank_account')}
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>

                <Card>
                    <CardContent className="pt-6">
                        {bankAccounts.length === 0 ? (
                            <div className="py-8 text-center">
                                <CreditCard className="mx-auto h-12 w-12 text-gray-400" />
                                <p className="mt-4 text-gray-500">{t('settings.no_bank_accounts')}</p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {bankAccounts.map((account) => (
                                    <div
                                        key={account.id}
                                        className="flex items-center justify-between p-4 border rounded-lg"
                                    >
                                        <div className="flex items-center gap-4">
                                            <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                {account.user_id ? (
                                                    <UserIcon className="w-5 h-5 text-blue-600" />
                                                ) : (
                                                    <Building2 className="w-5 h-5 text-blue-600" />
                                                )}
                                            </div>
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <span className="font-medium">{account.bank_name}</span>
                                                    {account.is_default && (
                                                        <Badge variant="success">{t('settings.default')}</Badge>
                                                    )}
                                                    <Badge variant="gray">{account.currency}</Badge>
                                                </div>
                                                <p className="text-sm text-gray-600">{account.account_number}</p>
                                                {account.iban && (
                                                    <p className="text-xs text-gray-500">IBAN: {account.iban}</p>
                                                )}
                                            </div>
                                        </div>
                                        <ActionDropdown
                                            actions={[
                                                {
                                                    label: t('settings.edit_bank_account'),
                                                    icon: Pencil,
                                                    onClick: () => handleEdit(account),
                                                },
                                                {
                                                    label: t('settings.delete_bank_account'),
                                                    icon: Trash2,
                                                    onClick: () => handleDelete(account.id),
                                                    variant: 'destructive',
                                                },
                                            ]}
                                        />
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </SettingsLayout>
    );
}

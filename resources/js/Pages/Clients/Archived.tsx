import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Components/AppLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import DeleteConfirmDialog from '@/Components/DeleteConfirmDialog';
import { useTranslation } from '@/hooks/use-translation';
import { formatDate } from '@/lib/utils';
import { ArrowLeft, RotateCcw, Trash2, Archive } from 'lucide-react';
import type { Client } from '@/types';

interface ArchivedClient extends Client {
    deleted_at: string;
}

interface ArchivedClientsProps {
    clients: ArchivedClient[];
}

export default function ArchivedClients({ clients }: ArchivedClientsProps) {
    const { t } = useTranslation();
    const [deleteClient, setDeleteClient] = useState<ArchivedClient | null>(null);

    const handleRestore = (id: number) => {
        router.post(`/clients/${id}/restore`);
    };

    return (
        <AppLayout>
            <Head title={t('clients.archived_title')} />

            <div>
                {/* Header */}
                <div className="mb-6">
                    <Button variant="ghost" size="sm" asChild className="mb-4">
                        <Link href="/clients" className="flex items-center gap-2">
                            <ArrowLeft className="w-4 h-4" />
                            {t('clients.back_to_list')}
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-bold text-gray-900">{t('clients.archived_title')}</h1>
                    <p className="mt-1 text-sm text-gray-500">{t('clients.archived_subtitle')}</p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Archive className="w-5 h-5" />
                            {t('clients.archived_clients')}
                        </CardTitle>
                        <CardDescription>{t('clients.archived_description')}</CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        {clients.length === 0 ? (
                            <div className="text-center py-12">
                                <Archive className="mx-auto h-12 w-12 text-gray-400" />
                                <p className="mt-4 text-gray-500">{t('clients.no_archived')}</p>
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{t('clients.client')}</TableHead>
                                        <TableHead className="hidden md:table-cell">{t('clients.contact')}</TableHead>
                                        <TableHead className="hidden md:table-cell">{t('clients.archived_date')}</TableHead>
                                        <TableHead className="text-right">{t('clients.actions')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {clients.map((client) => (
                                        <TableRow key={client.id}>
                                            <TableCell>
                                                <div>
                                                    <p className="text-sm font-medium text-gray-900">{client.name}</p>
                                                    {client.city && (
                                                        <p className="text-xs text-gray-400">{client.city}</p>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell className="hidden md:table-cell">
                                                <div className="text-sm text-gray-600">
                                                    {client.email && <p>{client.email}</p>}
                                                    {client.phone && <p className="text-gray-400">{client.phone}</p>}
                                                </div>
                                            </TableCell>
                                            <TableCell className="hidden md:table-cell">
                                                <span className="text-sm text-gray-500">
                                                    {formatDate(client.deleted_at)}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleRestore(client.id)}
                                                        className="flex items-center gap-1"
                                                    >
                                                        <RotateCcw className="w-4 h-4" />
                                                        {t('clients.restore')}
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => setDeleteClient(client)}
                                                    >
                                                        <Trash2 className="w-4 h-4 text-destructive" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>

            <DeleteConfirmDialog
                open={!!deleteClient}
                onOpenChange={() => setDeleteClient(null)}
                title={t('clients.delete_title')}
                description={t('clients.delete_description')}
                deleteUrl={deleteClient ? `/clients/${deleteClient.id}/force-delete` : ''}
            />
        </AppLayout>
    );
}

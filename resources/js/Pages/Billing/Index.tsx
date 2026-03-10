import { Head } from '@inertiajs/react';
import SettingsLayout from '@/Components/SettingsLayout';
import { useTranslation } from '@/hooks/use-translation';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { formatDate } from '@/lib/utils';
import { CreditCard, Calendar, Clock, Mail, Check } from 'lucide-react';

interface Props {
    subscriptionStatus: 'admin' | 'active' | 'trial' | 'expired';
    isActive: boolean;
    expiresAt: string | null;
    trialEndsAt: string | null;
    daysRemaining: number | null;
}

const statusVariant = (status: string) => {
    switch (status) {
        case 'active': return 'success';
        case 'trial': return 'info';
        case 'expired': return 'destructive';
        case 'admin': return 'secondary';
        default: return 'gray';
    }
};

export default function BillingIndex({ subscriptionStatus, expiresAt, trialEndsAt, daysRemaining }: Props) {
    const { t } = useTranslation();

    const features = [
        t('subscription.feature_invoices'),
        t('subscription.feature_clients'),
        t('subscription.feature_expenses'),
        t('subscription.feature_pdf'),
        t('subscription.feature_templates'),
        t('subscription.feature_multi_currency'),
    ];

    return (
        <SettingsLayout>
            <Head title={t('navigation.billing')} />
            <div className="max-w-4xl mx-auto">

                {/* Current status card */}
                <Card className="mb-8">
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <CardTitle className="text-lg flex items-center gap-2">
                                <CreditCard className="w-5 h-5" />
                                {t('subscription.subscription_status')}
                            </CardTitle>
                            <Badge variant={statusVariant(subscriptionStatus) as 'success' | 'info' | 'destructive' | 'secondary' | 'gray'}>
                                {t(`subscription.status_${subscriptionStatus}`)}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {subscriptionStatus === 'active' && expiresAt && (
                            <div className="flex items-center gap-3 text-sm">
                                <Calendar className="w-4 h-4 text-gray-400" />
                                <span className="text-gray-600">
                                    {t('subscription.active_until', { date: formatDate(expiresAt) })}
                                </span>
                            </div>
                        )}

                        {subscriptionStatus === 'trial' && trialEndsAt && (
                            <div className="flex items-center gap-3 text-sm">
                                <Calendar className="w-4 h-4 text-gray-400" />
                                <span className="text-gray-600">
                                    {t('subscription.trial_until', { date: formatDate(trialEndsAt) })}
                                </span>
                            </div>
                        )}

                        {daysRemaining !== null && daysRemaining > 0 && (
                            <div className="flex items-center gap-3 text-sm">
                                <Clock className="w-4 h-4 text-gray-400" />
                                <span className="text-gray-600">
                                    {t('subscription.days_remaining')}: <span className="font-medium">{daysRemaining}</span>
                                </span>
                            </div>
                        )}

                        {subscriptionStatus === 'expired' && (
                            <div className="p-4 bg-red-50 rounded-lg border border-red-200">
                                <p className="text-sm text-red-800">
                                    {t('subscription.expired_description')}
                                </p>
                            </div>
                        )}

                        {subscriptionStatus === 'admin' && (
                            <p className="text-sm text-gray-500">
                                {t('subscription.status_admin')}
                            </p>
                        )}
                    </CardContent>
                </Card>

                {/* Pricing plans */}
                {subscriptionStatus !== 'admin' && (
                    <>
                        <h2 className="text-xl font-bold text-gray-900 mb-2">{t('subscription.choose_plan')}</h2>
                        <p className="text-sm text-gray-500 mb-6">{t('subscription.choose_plan_description')}</p>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            {/* Monthly */}
                            <Card className="relative">
                                <CardHeader>
                                    <CardTitle className="text-lg">{t('subscription.monthly')}</CardTitle>
                                    <div className="flex items-baseline gap-1 mt-2">
                                        <span className="text-3xl font-bold text-gray-900">25&euro;</span>
                                        <span className="text-sm text-gray-500">{t('subscription.per_month')}</span>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <ul className="space-y-3">
                                        {features.map((feature) => (
                                            <li key={feature} className="flex items-center gap-2 text-sm text-gray-600">
                                                <Check className="w-4 h-4 text-green-500 shrink-0" />
                                                {feature}
                                            </li>
                                        ))}
                                    </ul>
                                </CardContent>
                            </Card>

                            {/* Yearly */}
                            <Card className="relative border-blue-200 shadow-md">
                                <div className="absolute -top-3 left-1/2 -translate-x-1/2">
                                    <Badge variant="info">{t('subscription.save_percent', { percent: '17' })}</Badge>
                                </div>
                                <CardHeader>
                                    <CardTitle className="text-lg">{t('subscription.yearly')}</CardTitle>
                                    <div className="flex items-baseline gap-1 mt-2">
                                        <span className="text-3xl font-bold text-gray-900">250&euro;</span>
                                        <span className="text-sm text-gray-500">{t('subscription.per_year')}</span>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <ul className="space-y-3">
                                        {features.map((feature) => (
                                            <li key={feature} className="flex items-center gap-2 text-sm text-gray-600">
                                                <Check className="w-4 h-4 text-green-500 shrink-0" />
                                                {feature}
                                            </li>
                                        ))}
                                    </ul>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Contact info */}
                        <Card>
                            <CardContent className="p-6">
                                <div className="text-center">
                                    <p className="text-sm text-gray-600 mb-3">
                                        {t('subscription.contact_to_subscribe')}
                                    </p>
                                    <div className="flex items-center justify-center gap-6 text-sm">
                                        <span className="flex items-center gap-2 text-gray-700">
                                            <Mail className="w-4 h-4 text-gray-400" />
                                            {t('subscription.email_contact')}
                                        </span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </>
                )}
            </div>
        </SettingsLayout>
    );
}

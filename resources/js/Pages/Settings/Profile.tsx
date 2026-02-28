import { FormEventHandler, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import SettingsLayout from '@/Components/SettingsLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { useTranslation } from '@/hooks/use-translation';
import type { User as UserType } from '@/types';

interface ProfileProps {
    user: UserType;
}

export default function Profile({ user }: ProfileProps) {
    const { t } = useTranslation();
    const [avatarPreview, setAvatarPreview] = useState<string | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        first_name: user.first_name || '',
        last_name: user.last_name || '',
        email: user.email,
        phone: user.phone || '',
        avatar: null as File | null,
        remove_avatar: false,
        _method: 'PATCH',
    });

    const { data: passwordData, setData: setPasswordData, post: postPassword, processing: processingPassword, errors: passwordErrors, reset: resetPassword } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const handleAvatarChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setData('avatar', file);
            setAvatarPreview(URL.createObjectURL(file));
        }
    };

    const removeAvatar = () => {
        setData('remove_avatar', true);
        setData('avatar', null);
        setAvatarPreview(null);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/profile', {
            forceFormData: true,
        });
    };

    const updatePassword: FormEventHandler = (e) => {
        e.preventDefault();
        postPassword('/password', {
            onSuccess: () => resetPassword(),
        });
    };

    const getInitials = () => {
        const first = data.first_name?.charAt(0) || '';
        const last = data.last_name?.charAt(0) || '';
        return (first + last).toUpperCase() || user.name?.charAt(0)?.toUpperCase() || '?';
    };

    return (
        <SettingsLayout>
            <Head title={t('settings.profile_title')} />

            <div>
                {/* Profile Form */}
                <form onSubmit={submit}>
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>{t('settings.profile_info')}</CardTitle>
                            <CardDescription>{t('settings.profile_info_desc')}</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {/* Avatar */}
                            <div className="flex items-center gap-6">
                                <Avatar className="w-20 h-20">
                                    <AvatarImage src={avatarPreview || (user.avatar ? `/storage/${user.avatar}` : undefined)} />
                                    <AvatarFallback className="text-xl">{getInitials()}</AvatarFallback>
                                </Avatar>
                                <div className="space-y-2">
                                    <div className="flex gap-2">
                                        <Button type="button" variant="outline" size="sm" asChild>
                                            <label className="cursor-pointer">
                                                {t('settings.change_avatar')}
                                                <input
                                                    type="file"
                                                    accept="image/*"
                                                    onChange={handleAvatarChange}
                                                    className="hidden"
                                                />
                                            </label>
                                        </Button>
                                        {(user.avatar || avatarPreview) && (
                                            <Button type="button" variant="outline" size="sm" onClick={removeAvatar}>
                                                {t('settings.remove_avatar')}
                                            </Button>
                                        )}
                                    </div>
                                    <p className="text-xs text-gray-500">{t('settings.avatar_hint')}</p>
                                </div>
                            </div>

                            {/* First Name & Last Name */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-md">
                                <div>
                                    <Label htmlFor="first_name">{t('settings.first_name')}</Label>
                                    <Input
                                        id="first_name"
                                        value={data.first_name}
                                        onChange={(e) => setData('first_name', e.target.value)}
                                        className="mt-1"
                                        error={errors.first_name}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="last_name">{t('settings.last_name')}</Label>
                                    <Input
                                        id="last_name"
                                        value={data.last_name}
                                        onChange={(e) => setData('last_name', e.target.value)}
                                        className="mt-1"
                                        error={errors.last_name}
                                    />
                                </div>
                            </div>

                            {/* Email */}
                            <div>
                                <Label htmlFor="email">{t('settings.email')} *</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    className="mt-1 max-w-md"
                                    error={errors.email}
                                />
                            </div>

                            {/* Phone */}
                            <div>
                                <Label htmlFor="phone">{t('settings.phone')}</Label>
                                <Input
                                    id="phone"
                                    type="tel"
                                    value={data.phone}
                                    onChange={(e) => setData('phone', e.target.value)}
                                    className="mt-1 max-w-md"
                                    error={errors.phone}
                                />
                            </div>

                            <div className="pt-4">
                                <Button type="submit" disabled={processing} loading={processing}>
                                    {t('settings.save_profile')}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>

                {/* Password Form */}
                <form onSubmit={updatePassword}>
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('settings.change_password')}</CardTitle>
                            <CardDescription>{t('settings.change_password_desc')}</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label htmlFor="current_password">{t('settings.current_password')} *</Label>
                                <Input
                                    id="current_password"
                                    type="password"
                                    value={passwordData.current_password}
                                    onChange={(e) => setPasswordData('current_password', e.target.value)}
                                    className="mt-1 max-w-md"
                                    error={passwordErrors.current_password}
                                />
                            </div>

                            <div>
                                <Label htmlFor="password">{t('settings.new_password')} *</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={passwordData.password}
                                    onChange={(e) => setPasswordData('password', e.target.value)}
                                    className="mt-1 max-w-md"
                                    error={passwordErrors.password}
                                />
                            </div>

                            <div>
                                <Label htmlFor="password_confirmation">{t('settings.confirm_password')} *</Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    value={passwordData.password_confirmation}
                                    onChange={(e) => setPasswordData('password_confirmation', e.target.value)}
                                    className="mt-1 max-w-md"
                                />
                            </div>

                            <div className="pt-4">
                                <Button type="submit" disabled={processingPassword} loading={processingPassword}>
                                    {t('settings.update_password')}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </SettingsLayout>
    );
}

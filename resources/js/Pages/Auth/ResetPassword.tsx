import { FormEventHandler } from 'react';
import { Head, useForm } from '@inertiajs/react';
import GuestLayout from '@/Components/GuestLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Mail, Lock } from 'lucide-react';

interface ResetPasswordProps {
    token: string;
    email: string;
}

export default function ResetPassword({ token, email }: ResetPasswordProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/reset-password', {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Ресетирај лозинка" />

            <div className="mb-8">
                <h1 className="text-2xl font-bold text-gray-900">Поставете нова лозинка</h1>
                <p className="mt-2 text-sm text-gray-500">
                    Внесете ја вашата нова лозинка подолу.
                </p>
            </div>

            <form onSubmit={submit} className="space-y-5">
                <div>
                    <Label htmlFor="email">Е-пошта</Label>
                    <div className="relative mt-1">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Mail className="h-4 w-4 text-gray-400" />
                        </div>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            value={data.email}
                            className="pl-10"
                            autoComplete="username"
                            onChange={(e) => setData('email', e.target.value)}
                            error={errors.email}
                        />
                    </div>
                </div>

                <div>
                    <Label htmlFor="password">Нова лозинка</Label>
                    <div className="relative mt-1">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Lock className="h-4 w-4 text-gray-400" />
                        </div>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            className="pl-10"
                            placeholder="Внесете нова лозинка"
                            autoComplete="new-password"
                            autoFocus
                            onChange={(e) => setData('password', e.target.value)}
                            error={errors.password}
                        />
                    </div>
                </div>

                <div>
                    <Label htmlFor="password_confirmation">Потврди лозинка</Label>
                    <div className="relative mt-1">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Lock className="h-4 w-4 text-gray-400" />
                        </div>
                        <Input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            value={data.password_confirmation}
                            className="pl-10"
                            placeholder="Потврдете ја новата лозинка"
                            autoComplete="new-password"
                            onChange={(e) =>
                                setData('password_confirmation', e.target.value)
                            }
                            error={errors.password_confirmation}
                        />
                    </div>
                </div>

                <Button className="w-full" size="lg" disabled={processing} loading={processing}>
                    Ресетирај лозинка
                </Button>
            </form>
        </GuestLayout>
    );
}

import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Components/GuestLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { User, Mail, Lock, Phone } from 'lucide-react';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/register', {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Register" />

            <div className="mb-8">
                <h1 className="text-2xl font-bold text-gray-900">Креирајте сметка</h1>
                <p className="mt-2 text-sm text-gray-500">
                    Регистрирајте се за да започнете
                </p>
            </div>

            <form onSubmit={submit} className="space-y-5">
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <Label htmlFor="first_name">Име</Label>
                        <div className="relative mt-1">
                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <User className="h-4 w-4 text-gray-400" />
                            </div>
                            <Input
                                id="first_name"
                                name="first_name"
                                value={data.first_name}
                                className="pl-10"
                                placeholder="Име"
                                autoComplete="given-name"
                                autoFocus
                                onChange={(e) => setData('first_name', e.target.value)}
                                error={errors.first_name}
                            />
                        </div>
                    </div>
                    <div>
                        <Label htmlFor="last_name">Презиме</Label>
                        <div className="relative mt-1">
                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <User className="h-4 w-4 text-gray-400" />
                            </div>
                            <Input
                                id="last_name"
                                name="last_name"
                                value={data.last_name}
                                className="pl-10"
                                placeholder="Презиме"
                                autoComplete="family-name"
                                onChange={(e) => setData('last_name', e.target.value)}
                                error={errors.last_name}
                            />
                        </div>
                    </div>
                </div>

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
                            placeholder="name@example.com"
                            autoComplete="username"
                            onChange={(e) => setData('email', e.target.value)}
                            error={errors.email}
                        />
                    </div>
                </div>

                <div>
                    <Label htmlFor="phone">Телефонски број</Label>
                    <div className="relative mt-1">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Phone className="h-4 w-4 text-gray-400" />
                        </div>
                        <Input
                            id="phone"
                            type="tel"
                            name="phone"
                            value={data.phone}
                            className="pl-10"
                            placeholder="+389 XX XXX XXX"
                            autoComplete="tel"
                            onChange={(e) => setData('phone', e.target.value)}
                            error={errors.phone}
                        />
                    </div>
                </div>

                <div>
                    <Label htmlFor="password">Лозинка</Label>
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
                            placeholder="Внесете лозинка"
                            autoComplete="new-password"
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
                            placeholder="Потврдете ја лозинката"
                            autoComplete="new-password"
                            onChange={(e) =>
                                setData('password_confirmation', e.target.value)
                            }
                            error={errors.password_confirmation}
                        />
                    </div>
                </div>

                <Button className="w-full" size="lg" disabled={processing} loading={processing}>
                    Регистрирај се
                </Button>

                <p className="text-center text-sm text-gray-500">
                    Веќе имате сметка?{' '}
                    <Link href="/login" className="font-medium text-blue-600 hover:text-blue-700">
                        Најавете се
                    </Link>
                </p>
            </form>
        </GuestLayout>
    );
}

import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Components/GuestLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Checkbox } from '@/Components/ui/checkbox';
import { Mail, Lock } from 'lucide-react';

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ status, canResetPassword }: LoginProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/login', {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Log in" />

            <div className="mb-8">
                <h1 className="text-2xl font-bold text-gray-900">Welcome back</h1>
                <p className="mt-2 text-sm text-gray-500">
                    Sign in to your account to continue
                </p>
            </div>

            {status && (
                <div className="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 text-sm font-medium text-green-700">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-5">
                <div>
                    <Label htmlFor="email">Email</Label>
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
                            autoFocus
                            onChange={(e) => setData('email', e.target.value)}
                            error={errors.email}
                        />
                    </div>
                </div>

                <div>
                    <div className="flex items-center justify-between">
                        <Label htmlFor="password">Password</Label>
                        {canResetPassword && (
                            <Link
                                href="/forgot-password"
                                className="text-xs text-blue-600 hover:text-blue-700 font-medium"
                            >
                                Forgot password?
                            </Link>
                        )}
                    </div>
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
                            placeholder="Enter your password"
                            autoComplete="current-password"
                            onChange={(e) => setData('password', e.target.value)}
                            error={errors.password}
                        />
                    </div>
                </div>

                <div className="flex items-center">
                    <Checkbox
                        id="remember"
                        checked={data.remember}
                        onCheckedChange={(checked) =>
                            setData('remember', checked as boolean)
                        }
                    />
                    <Label htmlFor="remember" className="ml-2 cursor-pointer text-sm text-gray-600">
                        Remember me
                    </Label>
                </div>

                <Button className="w-full" size="lg" disabled={processing} loading={processing}>
                    Sign in
                </Button>

                <p className="text-center text-sm text-gray-500">
                    Don&apos;t have an account?{' '}
                    <Link href="/register" className="font-medium text-blue-600 hover:text-blue-700">
                        Create one
                    </Link>
                </p>
            </form>
        </GuestLayout>
    );
}

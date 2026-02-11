import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Components/GuestLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { User, Mail, Lock } from 'lucide-react';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
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
                <h1 className="text-2xl font-bold text-gray-900">Create your account</h1>
                <p className="mt-2 text-sm text-gray-500">
                    Get started with your free account
                </p>
            </div>

            <form onSubmit={submit} className="space-y-5">
                <div>
                    <Label htmlFor="name">Name</Label>
                    <div className="relative mt-1">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <User className="h-4 w-4 text-gray-400" />
                        </div>
                        <Input
                            id="name"
                            name="name"
                            value={data.name}
                            className="pl-10"
                            placeholder="Your full name"
                            autoComplete="name"
                            autoFocus
                            onChange={(e) => setData('name', e.target.value)}
                            error={errors.name}
                        />
                    </div>
                </div>

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
                            onChange={(e) => setData('email', e.target.value)}
                            error={errors.email}
                        />
                    </div>
                </div>

                <div>
                    <Label htmlFor="password">Password</Label>
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
                            placeholder="Create a password"
                            autoComplete="new-password"
                            onChange={(e) => setData('password', e.target.value)}
                            error={errors.password}
                        />
                    </div>
                </div>

                <div>
                    <Label htmlFor="password_confirmation">Confirm Password</Label>
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
                            placeholder="Confirm your password"
                            autoComplete="new-password"
                            onChange={(e) =>
                                setData('password_confirmation', e.target.value)
                            }
                            error={errors.password_confirmation}
                        />
                    </div>
                </div>

                <Button className="w-full" size="lg" disabled={processing} loading={processing}>
                    Create account
                </Button>

                <p className="text-center text-sm text-gray-500">
                    Already have an account?{' '}
                    <Link href="/login" className="font-medium text-blue-600 hover:text-blue-700">
                        Sign in
                    </Link>
                </p>
            </form>
        </GuestLayout>
    );
}

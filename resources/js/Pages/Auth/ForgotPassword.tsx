import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Components/GuestLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Mail } from 'lucide-react';

interface ForgotPasswordProps {
    status?: string;
}

export default function ForgotPassword({ status }: ForgotPasswordProps) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/forgot-password');
    };

    return (
        <GuestLayout>
            <Head title="Заборавена лозинка" />

            <div className="mb-8">
                <h1 className="text-2xl font-bold text-gray-900">Ресетирај лозинка</h1>
                <p className="mt-2 text-sm text-gray-500">
                    Внесете ја вашата е-пошта и ќе ви испратиме линк за ресетирање на лозинката.
                </p>
            </div>

            {status && (
                <div className="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 text-sm font-medium text-green-700">
                    {status}
                </div>
            )}

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
                            placeholder="name@example.com"
                            autoFocus
                            onChange={(e) => setData('email', e.target.value)}
                            error={errors.email}
                        />
                    </div>
                </div>

                <Button className="w-full" size="lg" disabled={processing} loading={processing}>
                    Испрати линк за ресетирање
                </Button>

                <p className="text-center text-sm text-gray-500">
                    Ја знаете лозинката?{' '}
                    <Link href="/login" className="font-medium text-blue-600 hover:text-blue-700">
                        Назад кон најава
                    </Link>
                </p>
            </form>
        </GuestLayout>
    );
}

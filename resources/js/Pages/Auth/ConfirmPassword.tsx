import { FormEventHandler } from 'react';
import { Head, useForm } from '@inertiajs/react';
import GuestLayout from '@/Components/GuestLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/confirm-password', {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Потврди лозинка" />

            <div className="mb-4 text-sm text-gray-600">
                Ова е заштитен дел од апликацијата. Ве молиме потврдете ја
                вашата лозинка пред да продолжите.
            </div>

            <form onSubmit={submit}>
                <div>
                    <Label htmlFor="password">Лозинка</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1"
                        autoFocus
                        onChange={(e) => setData('password', e.target.value)}
                        error={errors.password}
                    />
                </div>

                <div className="mt-4 flex items-center justify-end">
                    <Button disabled={processing} loading={processing}>
                        Потврди
                    </Button>
                </div>
            </form>
        </GuestLayout>
    );
}

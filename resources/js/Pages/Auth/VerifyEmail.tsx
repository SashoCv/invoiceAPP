import { FormEventHandler } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Components/GuestLayout';
import { Button } from '@/Components/ui/button';

interface VerifyEmailProps {
    status?: string;
}

export default function VerifyEmail({ status }: VerifyEmailProps) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/email/verification-notification');
    };

    return (
        <GuestLayout>
            <Head title="Верификација на е-пошта" />

            <div className="mb-4 text-sm text-gray-600">
                Ви благодариме за регистрацијата! Пред да започнете, ве молиме
                верифицирајте ја вашата е-пошта со кликнување на линкот што ви
                го испративме. Доколку не го добивте мејлот, со задоволство ќе
                ви испратиме нов.
            </div>

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    Нов линк за верификација е испратен на е-поштата што ја
                    внесовте при регистрацијата.
                </div>
            )}

            <form onSubmit={submit}>
                <div className="mt-4 flex items-center justify-between">
                    <Button disabled={processing} loading={processing}>
                        Испрати повторно
                    </Button>

                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="text-sm text-gray-600 underline hover:text-gray-900"
                    >
                        Одјави се
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}

import { PropsWithChildren } from 'react';
import { Link } from '@inertiajs/react';
import { FileText } from 'lucide-react';

export default function GuestLayout({ children }: PropsWithChildren) {
    return (
        <div className="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <Link href="/" className="flex items-center gap-3">
                    <div className="w-16 h-16 rounded-xl bg-blue-600 flex items-center justify-center">
                        <FileText className="w-8 h-8 text-white" />
                    </div>
                </Link>
            </div>

            <div className="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {children}
            </div>
        </div>
    );
}

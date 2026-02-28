import { PropsWithChildren } from 'react';
import { Link } from '@inertiajs/react';
import { BarChart3, Receipt, TrendingUp, Warehouse } from 'lucide-react';
import FynvoLogo from '@/Components/FynvoLogo';

export default function GuestLayout({ children }: PropsWithChildren) {
    return (
        <div className="min-h-screen flex">
            {/* Left panel — decorative */}
            <div className="hidden lg:flex lg:w-1/2 relative bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 overflow-hidden">
                {/* Background pattern */}
                <div className="absolute inset-0 opacity-10">
                    <svg className="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                                <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" strokeWidth="0.5" />
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#grid)" />
                    </svg>
                </div>

                {/* Floating shapes */}
                <div className="absolute top-20 left-16 w-72 h-72 bg-white/5 rounded-full blur-3xl" />
                <div className="absolute bottom-32 right-12 w-96 h-96 bg-indigo-400/10 rounded-full blur-3xl" />
                <div className="absolute top-1/2 left-1/3 w-48 h-48 bg-blue-300/10 rounded-full blur-2xl" />

                {/* Content */}
                <div className="relative z-10 flex flex-col justify-between p-12 w-full">
                    {/* Logo */}
                    <div>
                        <Link href="/">
                            <FynvoLogo size={48} className="text-white/20 backdrop-blur-sm" textClassName="text-white text-2xl" />
                        </Link>
                    </div>

                    {/* Feature cards */}
                    <div className="space-y-6 max-w-md">
                        <h2 className="text-3xl font-bold text-white leading-tight">
                            Manage your invoices with ease
                        </h2>
                        <p className="text-blue-100/80 text-lg">
                            Create, send, and track invoices, offers, and proforma invoices — all in one place.
                        </p>

                        <div className="space-y-4 pt-4">
                            <div className="flex items-center gap-4 bg-white/10 backdrop-blur-sm rounded-xl p-4">
                                <div className="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center flex-shrink-0">
                                    <Receipt className="w-5 h-5 text-white" />
                                </div>
                                <div>
                                    <p className="text-white font-medium">Professional Invoices</p>
                                    <p className="text-blue-200/70 text-sm">Beautiful templates, PDF export, email delivery</p>
                                </div>
                            </div>

                            <div className="flex items-center gap-4 bg-white/10 backdrop-blur-sm rounded-xl p-4">
                                <div className="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center flex-shrink-0">
                                    <BarChart3 className="w-5 h-5 text-white" />
                                </div>
                                <div>
                                    <p className="text-white font-medium">Expense Tracking</p>
                                    <p className="text-blue-200/70 text-sm">Track expenses, recurring costs, categories</p>
                                </div>
                            </div>

                            <div className="flex items-center gap-4 bg-white/10 backdrop-blur-sm rounded-xl p-4">
                                <div className="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center flex-shrink-0">
                                    <Warehouse className="w-5 h-5 text-white" />
                                </div>
                                <div>
                                    <p className="text-white font-medium">Inventory Management</p>
                                    <p className="text-blue-200/70 text-sm">Stock tracking, bundles, warehouse dashboard</p>
                                </div>
                            </div>

                            <div className="flex items-center gap-4 bg-white/10 backdrop-blur-sm rounded-xl p-4">
                                <div className="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center flex-shrink-0">
                                    <TrendingUp className="w-5 h-5 text-white" />
                                </div>
                                <div>
                                    <p className="text-white font-medium">Dashboard Analytics</p>
                                    <p className="text-blue-200/70 text-sm">Revenue insights, multi-currency support</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Bottom */}
                    <p className="text-blue-200/50 text-sm">
                        &copy; {new Date().getFullYear()} Fynvo
                    </p>
                </div>
            </div>

            {/* Right panel — form */}
            <div className="flex-1 flex flex-col justify-center items-center px-6 py-12 bg-gray-50 lg:px-12">
                {/* Mobile logo */}
                <div className="lg:hidden mb-8">
                    <Link href="/">
                        <FynvoLogo size={48} className="text-blue-600" textClassName="text-gray-900 text-2xl" />
                    </Link>
                </div>

                <div className="w-full max-w-md">
                    {children}
                </div>
            </div>
        </div>
    );
}

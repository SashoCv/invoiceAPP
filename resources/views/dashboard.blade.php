<x-app-layout>
    <div>
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('dashboard.title') }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ __('dashboard.subtitle') }}</p>
            </div>
            <p class="hidden sm:block text-sm text-gray-400">{{ now()->translatedFormat('d F Y') }}</p>
        </div>
            <!-- Main Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Invoices -->
                <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg p-6 text-white">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-16 h-16 bg-white/10 rounded-full"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100 text-sm font-medium">{{ __('dashboard.total_invoices') }}</p>
                                <p class="text-4xl font-bold mt-2">{{ $totalInvoices }}</p>
                            </div>
                            <div class="bg-white/20 rounded-xl p-3">
                                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-blue-100 text-sm">
                            <span class="font-medium">{{ $totalClients }} {{ __('dashboard.clients') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Total Revenue -->
                <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl shadow-lg p-6 text-white">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-16 h-16 bg-white/10 rounded-full"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-emerald-100 text-sm font-medium">{{ __('dashboard.collected') }}</p>
                                <p class="text-3xl font-bold mt-2">{{ number_format($totalRevenue, 0, ',', '.') }}</p>
                                <p class="text-emerald-200 text-sm">{{ __('dashboard.currency') }}</p>
                            </div>
                            <div class="bg-white/20 rounded-xl p-3">
                                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-emerald-100 text-sm">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-medium">{{ $paidInvoices }} {{ __('dashboard.paid') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Pending Amount -->
                <div class="relative overflow-hidden bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl shadow-lg p-6 text-white">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-16 h-16 bg-white/10 rounded-full"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-amber-100 text-sm font-medium">{{ __('dashboard.pending_payment') }}</p>
                                <p class="text-3xl font-bold mt-2">{{ number_format($pendingAmount, 0, ',', '.') }}</p>
                                <p class="text-amber-200 text-sm">{{ __('dashboard.currency') }}</p>
                            </div>
                            <div class="bg-white/20 rounded-xl p-3">
                                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-amber-100 text-sm">
                            <span class="font-medium">{{ $pendingInvoices }} {{ __('dashboard.invoices') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Overdue Amount -->
                <div class="relative overflow-hidden bg-gradient-to-br from-rose-500 to-red-600 rounded-2xl shadow-lg p-6 text-white">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-16 h-16 bg-white/10 rounded-full"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-rose-100 text-sm font-medium">{{ __('dashboard.overdue') }}</p>
                                <p class="text-3xl font-bold mt-2">{{ number_format($overdueAmount, 0, ',', '.') }}</p>
                                <p class="text-rose-200 text-sm">{{ __('dashboard.currency') }}</p>
                            </div>
                            <div class="bg-white/20 rounded-xl p-3">
                                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-rose-100 text-sm">
                            <span class="font-medium">{{ $overdueInvoices }} {{ __('dashboard.invoices') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Monthly Revenue Chart -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ __('dashboard.monthly_revenue') }}</h3>
                                <p class="text-sm text-gray-500 mt-1">{{ __('dashboard.last_6_months') }}</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-50 text-emerald-700">
                                    <span class="w-2 h-2 bg-emerald-500 rounded-full mr-1.5"></span>
                                    {{ __('dashboard.revenue') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-5">
                            @php $maxRevenue = max(array_column($monthlyRevenue, 'revenue')) ?: 1; @endphp
                            @foreach($monthlyRevenue as $month)
                                <div class="group">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-700">{{ $month['month'] }}</span>
                                        <span class="text-sm font-bold text-gray-900">{{ number_format($month['revenue'], 0, ',', '.') }} {{ __('dashboard.currency') }}</span>
                                    </div>
                                    <div class="relative">
                                        <div class="overflow-hidden h-3 rounded-full bg-gray-100">
                                            @php $percentage = ($month['revenue'] / $maxRevenue) * 100; @endphp
                                            <div class="h-3 rounded-full bg-gradient-to-r from-emerald-400 to-emerald-500 transition-all duration-500 ease-out group-hover:from-emerald-500 group-hover:to-emerald-600"
                                                 style="width: {{ $percentage }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Status Distribution -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">{{ __('dashboard.invoice_status') }}</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ __('dashboard.status_distribution') }}</p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-6">
                            <!-- Paid -->
                            <div class="flex items-center">
                                <div class="flex-shrink-0 w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900">{{ __('dashboard.paid_invoices') }}</p>
                                        <p class="text-lg font-bold text-emerald-600">{{ $statusDistribution['paid'] }}</p>
                                    </div>
                                    <div class="mt-1 overflow-hidden h-2 rounded-full bg-gray-100">
                                        @php $paidPercent = $totalInvoices > 0 ? ($statusDistribution['paid'] / $totalInvoices) * 100 : 0; @endphp
                                        <div class="h-2 rounded-full bg-emerald-500" style="width: {{ $paidPercent }}%"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pending -->
                            <div class="flex items-center">
                                <div class="flex-shrink-0 w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900">{{ __('dashboard.pending_invoices') }}</p>
                                        <p class="text-lg font-bold text-amber-600">{{ $statusDistribution['pending'] }}</p>
                                    </div>
                                    <div class="mt-1 overflow-hidden h-2 rounded-full bg-gray-100">
                                        @php $pendingPercent = $totalInvoices > 0 ? ($statusDistribution['pending'] / $totalInvoices) * 100 : 0; @endphp
                                        <div class="h-2 rounded-full bg-amber-500" style="width: {{ $pendingPercent }}%"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Overdue -->
                            <div class="flex items-center">
                                <div class="flex-shrink-0 w-12 h-12 bg-rose-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                                <div class="ml-4 flex-1">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900">{{ __('dashboard.overdue_invoices') }}</p>
                                        <p class="text-lg font-bold text-rose-600">{{ $statusDistribution['overdue'] }}</p>
                                    </div>
                                    <div class="mt-1 overflow-hidden h-2 rounded-full bg-gray-100">
                                        @php $overduePercent = $totalInvoices > 0 ? ($statusDistribution['overdue'] / $totalInvoices) * 100 : 0; @endphp
                                        <div class="h-2 rounded-full bg-rose-500" style="width: {{ $overduePercent }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Summary -->
                        <div class="mt-6 pt-6 border-t border-gray-100">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">{{ __('dashboard.collection_rate') }}</span>
                                <span class="font-bold text-gray-900">
                                    @php $successRate = $totalInvoices > 0 ? round(($statusDistribution['paid'] / $totalInvoices) * 100) : 0; @endphp
                                    {{ $successRate }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Invoices -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ __('dashboard.recent_invoices') }}</h3>
                            <p class="text-sm text-gray-500 mt-1">{{ __('dashboard.recent_invoices_subtitle') }}</p>
                        </div>
                        <a href="{{ route('invoices.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                            {{ __('dashboard.view_all') }}
                            <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
                <div>
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="hidden md:table-cell px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('dashboard.invoice') }}</th>
                                <th class="px-4 md:px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('dashboard.client') }}</th>
                                <th class="hidden md:table-cell px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('dashboard.date') }}</th>
                                <th class="px-4 md:px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('dashboard.amount') }}</th>
                                <th class="px-4 md:px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ __('dashboard.status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($recentInvoices as $invoice)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm font-semibold text-gray-900">{{ $invoice->invoice_number }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $invoice->client->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $invoice->client->company ?? $invoice->client->email }}</p>
                                        </div>
                                    </td>
                                    <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap">
                                        <p class="text-sm text-gray-900">{{ $invoice->issue_date->format('d.m.Y') }}</p>
                                        <p class="text-xs text-gray-500">{{ __('dashboard.due') }}: {{ $invoice->due_date->format('d.m.Y') }}</p>
                                    </td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                        <p class="text-sm font-bold text-gray-900">{{ number_format($invoice->total, 0, ',', '.') }} {{ __('dashboard.currency') }}</p>
                                    </td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                        @switch($invoice->status)
                                            @case('paid')
                                                <!-- Mobile: just dot -->
                                                <span class="md:hidden inline-block w-3 h-3 rounded-full bg-emerald-500"></span>
                                                <!-- Desktop: full badge -->
                                                <span class="hidden md:inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-1.5"></span>
                                                    {{ __('dashboard.status_paid') }}
                                                </span>
                                                @break
                                            @case('sent')
                                                <span class="md:hidden inline-block w-3 h-3 rounded-full bg-blue-500"></span>
                                                <span class="hidden md:inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                                    <span class="w-1.5 h-1.5 bg-blue-500 rounded-full mr-1.5"></span>
                                                    {{ __('dashboard.status_sent') }}
                                                </span>
                                                @break
                                            @case('draft')
                                                <span class="md:hidden inline-block w-3 h-3 rounded-full bg-gray-400"></span>
                                                <span class="hidden md:inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                                    <span class="w-1.5 h-1.5 bg-gray-500 rounded-full mr-1.5"></span>
                                                    {{ __('dashboard.status_draft') }}
                                                </span>
                                                @break
                                            @case('overdue')
                                                <span class="md:hidden inline-block w-3 h-3 rounded-full bg-rose-500"></span>
                                                <span class="hidden md:inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">
                                                    <span class="w-1.5 h-1.5 bg-rose-500 rounded-full mr-1.5"></span>
                                                    {{ __('dashboard.status_overdue') }}
                                                </span>
                                                @break
                                            @case('cancelled')
                                                <span class="md:hidden inline-block w-3 h-3 rounded-full bg-gray-400"></span>
                                                <span class="hidden md:inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full mr-1.5"></span>
                                                    {{ __('dashboard.status_cancelled') }}
                                                </span>
                                                @break
                                        @endswitch
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
    </div>
</x-app-layout>

<x-app-layout>
    <div>
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('invoices.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('invoices.subtitle') }}</p>
            </div>
            @if(!$showDeleted)
                <a href="{{ route('invoices.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('invoices.new_invoice') }}
                </a>
            @endif
        </div>

        <!-- Tabs: Active / Deleted -->
        <div class="flex gap-4 mb-6 border-b border-gray-200">
            <a href="{{ route('invoices.index') }}"
               class="pb-3 px-1 text-sm font-medium border-b-2 transition-colors {{ !$showDeleted ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ __('invoices.active_invoices') }}
            </a>
            <a href="{{ route('invoices.index', ['deleted' => 1]) }}"
               class="pb-3 px-1 text-sm font-medium border-b-2 transition-colors {{ $showDeleted ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ __('invoices.deleted_invoices') }}
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
            <form method="GET" action="{{ route('invoices.index') }}" id="filterForm">
                <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                @if($showDeleted)
                    <input type="hidden" name="deleted" value="1">
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('invoices.search') }}</label>
                        <div class="relative">
                            <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" name="invoice" value="{{ request('invoice') }}"
                                   placeholder="{{ __('invoices.search_placeholder') }}"
                                   class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <!-- Client -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('invoices.client') }}</label>
                        <select name="client"
                                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">{{ __('invoices.all_clients') }}</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ request('client') == $client->id ? 'selected' : '' }}>
                                    {{ $client->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('invoices.date_from') }}</label>
                        <input type="text" name="date_from"
                               value="{{ request('date_from') }}"
                               placeholder="дд.мм.гггг"
                               data-datepicker
                               readonly
                               class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white cursor-pointer">
                    </div>

                    <!-- Date To -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('invoices.date_to') }}</label>
                        <input type="text" name="date_to"
                               value="{{ request('date_to') }}"
                               placeholder="дд.мм.гггг"
                               data-datepicker
                               readonly
                               class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white cursor-pointer">
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('invoices.status') }}</label>
                        <select name="status"
                                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">{{ __('invoices.all_statuses') }}</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>{{ __('invoices.status_draft') }}</option>
                            <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>{{ __('invoices.status_sent') }}</option>
                            <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>{{ __('invoices.status_unpaid') }}</option>
                            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>{{ __('invoices.status_paid') }}</option>
                            <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>{{ __('invoices.status_overdue') }}</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('invoices.status_cancelled') }}</option>
                        </select>
                    </div>

                    <!-- Actions -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">&nbsp;</label>
                        <div class="flex gap-2">
                            <button type="submit" class="px-5 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                                {{ __('invoices.filter') }}
                            </button>
                            @if(request()->hasAny(['invoice', 'client', 'date_from', 'date_to', 'status']))
                                <a href="{{ route('invoices.index', $showDeleted ? ['deleted' => 1] : []) }}"
                                   class="px-4 py-2.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    {{ __('invoices.clear_filters') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Invoices List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            @if($invoices->isEmpty())
                <div class="text-center py-12">
                    @if($showDeleted)
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <p class="mt-4 text-gray-500">{{ __('invoices.no_deleted_invoices') }}</p>
                    @else
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="mt-4 text-gray-500">{{ __('invoices.no_invoices') }}</p>
                        <a href="{{ route('invoices.create') }}" class="mt-4 inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                            {{ __('invoices.create_first') }}
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('invoices.invoice') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                                    {{ __('invoices.client') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                                    {{ __('invoices.date') }}
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('invoices.status') }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('invoices.total') }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('invoices.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($invoices as $invoice)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <a href="{{ route('invoices.show', $invoice) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                            {{ $invoice->formatted_number }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 hidden md:table-cell">
                                        <span class="text-sm text-gray-900">{{ $invoice->client?->display_name ?? '-' }}</span>
                                    </td>
                                    <td class="px-6 py-4 hidden lg:table-cell">
                                        <span class="text-sm text-gray-600">{{ $invoice->issue_date->format('d.m.Y') }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @php
                                            $statusColors = [
                                                'draft' => 'bg-gray-100 text-gray-700',
                                                'sent' => 'bg-blue-100 text-blue-700',
                                                'unpaid' => 'bg-yellow-100 text-yellow-700',
                                                'paid' => 'bg-green-100 text-green-700',
                                                'overdue' => 'bg-amber-100 text-amber-700',
                                                'cancelled' => 'bg-red-100 text-red-700',
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$invoice->status] ?? 'bg-gray-100 text-gray-700' }}">
                                            {{ __('invoices.status_' . $invoice->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="text-sm font-medium text-gray-900">{{ number_format($invoice->total, 2) }} ден.</span>
                                    </td>
                                    <td class="px-6 py-4 text-right" x-data="{ deleteModal: false }">
                                        @if($showDeleted)
                                            <!-- Actions for deleted invoices -->
                                            <div class="flex items-center justify-end gap-1">
                                                <form action="{{ route('invoices.restore', $invoice->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                            title="{{ __('invoices.restore') }}"
                                                            class="p-2 text-gray-400 hover:text-green-600 rounded-lg hover:bg-green-50 transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                                <button type="button" @click="deleteModal = true"
                                                        title="{{ __('invoices.delete_permanently') }}"
                                                        class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Force Delete Modal -->
                                            <div x-show="deleteModal" x-cloak
                                                 class="fixed inset-0 z-[100] overflow-y-auto"
                                                 x-transition:enter="ease-out duration-300"
                                                 x-transition:enter-start="opacity-0"
                                                 x-transition:enter-end="opacity-100"
                                                 x-transition:leave="ease-in duration-200"
                                                 x-transition:leave-start="opacity-100"
                                                 x-transition:leave-end="opacity-0">
                                                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
                                                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="deleteModal = false"></div>

                                                    <div class="relative bg-white rounded-xl shadow-xl w-80 p-6 z-10">
                                                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 mb-4">
                                                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                            </svg>
                                                        </div>
                                                        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('invoices.delete_permanently') }}</h3>
                                                        <p class="text-sm text-gray-600 mb-2">{{ $invoice->formatted_number }}</p>
                                                        <p class="text-sm text-gray-600 mb-6">{{ __('invoices.delete_permanently_confirm') }}</p>
                                                        <div class="flex gap-3">
                                                            <button type="button" @click="deleteModal = false"
                                                                    class="flex-1 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                                                                {{ __('invoices.cancel') }}
                                                            </button>
                                                            <form action="{{ route('invoices.force-delete', $invoice->id) }}" method="POST" class="flex-1">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                        class="w-full px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                                                                    {{ __('invoices.delete') }}
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <!-- Actions for active invoices -->
                                            <div class="flex items-center justify-end gap-1">
                                                <a href="{{ route('invoices.show', $invoice) }}"
                                                   title="{{ __('invoices.view') }}"
                                                   class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                </a>
                                                <a href="{{ route('invoices.duplicate', $invoice) }}"
                                                   title="{{ __('invoices.duplicate') }}"
                                                   class="p-2 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                    </svg>
                                                </a>
                                                <a href="{{ route('invoices.edit', $invoice) }}"
                                                   title="{{ __('invoices.edit_invoice') }}"
                                                   class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </a>
                                                <button type="button" @click="deleteModal = true"
                                                        title="{{ __('invoices.delete') }}"
                                                        class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Delete Modal -->
                                            <div x-show="deleteModal" x-cloak
                                                 class="fixed inset-0 z-[100] overflow-y-auto"
                                                 x-transition:enter="ease-out duration-300"
                                                 x-transition:enter-start="opacity-0"
                                                 x-transition:enter-end="opacity-100"
                                                 x-transition:leave="ease-in duration-200"
                                                 x-transition:leave-start="opacity-100"
                                                 x-transition:leave-end="opacity-0">
                                                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
                                                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="deleteModal = false"></div>

                                                    <div class="relative bg-white rounded-xl shadow-xl w-80 p-6 z-10">
                                                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 mb-4">
                                                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                            </svg>
                                                        </div>
                                                        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('invoices.delete_invoice') }}</h3>
                                                        <p class="text-sm text-gray-600 mb-2">{{ $invoice->formatted_number }}</p>
                                                        <p class="text-sm text-gray-600 mb-6">{{ __('invoices.delete_confirm') }}</p>
                                                        <div class="flex gap-3">
                                                            <button type="button" @click="deleteModal = false"
                                                                    class="flex-1 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                                                                {{ __('invoices.cancel') }}
                                                            </button>
                                                            <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" class="flex-1">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                        class="w-full px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                                                                    {{ __('invoices.delete') }}
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <!-- Left: Showing info -->
                        <p class="text-sm text-gray-600">
                            {{ __('invoices.showing') }}
                            <span class="font-semibold text-gray-900">{{ $invoices->firstItem() ?? 0 }}</span>
                            -
                            <span class="font-semibold text-gray-900">{{ $invoices->lastItem() ?? 0 }}</span>
                            {{ __('invoices.of') }}
                            <span class="font-semibold text-gray-900">{{ $invoices->total() }}</span>
                        </p>

                        <!-- Right: Per page + Navigation -->
                        <div class="flex items-center gap-6">
                            <!-- Per Page -->
                            <div class="hidden sm:flex items-center gap-2 text-sm text-gray-600">
                                <span>{{ __('invoices.per_page') }}:</span>
                                <select onchange="window.location.href='{{ route('invoices.index') }}?' + new URLSearchParams({...Object.fromEntries(new URLSearchParams(window.location.search)), per_page: this.value, page: 1}).toString()"
                                        class="border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                                    @foreach([10, 25, 50, 100] as $num)
                                        <option value="{{ $num }}" {{ request('per_page', 10) == $num ? 'selected' : '' }}>{{ $num }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Page Navigation -->
                            @if($invoices->hasPages())
                                <nav class="flex items-center gap-1">
                                    {{-- Previous --}}
                                    <a href="{{ $invoices->onFirstPage() ? '#' : $invoices->previousPageUrl() }}"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-md border {{ $invoices->onFirstPage() ? 'border-gray-200 bg-gray-100 text-gray-400 pointer-events-none' : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                        </svg>
                                    </a>

                                    {{-- Page Numbers --}}
                                    @if($invoices->currentPage() > 3)
                                        <a href="{{ $invoices->url(1) }}" class="inline-flex items-center justify-center w-8 h-8 text-sm rounded-md border border-gray-300 bg-white text-gray-600 hover:bg-gray-50">1</a>
                                        @if($invoices->currentPage() > 4)
                                            <span class="w-8 text-center text-gray-400">...</span>
                                        @endif
                                    @endif

                                    @foreach(range(max(1, $invoices->currentPage() - 1), min($invoices->lastPage(), $invoices->currentPage() + 1)) as $page)
                                        <a href="{{ $invoices->url($page) }}"
                                           class="inline-flex items-center justify-center w-8 h-8 text-sm rounded-md border {{ $page == $invoices->currentPage() ? 'border-blue-600 bg-blue-600 text-white font-medium' : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50' }}">
                                            {{ $page }}
                                        </a>
                                    @endforeach

                                    @if($invoices->currentPage() < $invoices->lastPage() - 2)
                                        @if($invoices->currentPage() < $invoices->lastPage() - 3)
                                            <span class="w-8 text-center text-gray-400">...</span>
                                        @endif
                                        <a href="{{ $invoices->url($invoices->lastPage()) }}" class="inline-flex items-center justify-center w-8 h-8 text-sm rounded-md border border-gray-300 bg-white text-gray-600 hover:bg-gray-50">{{ $invoices->lastPage() }}</a>
                                    @endif

                                    {{-- Next --}}
                                    <a href="{{ $invoices->hasMorePages() ? $invoices->nextPageUrl() : '#' }}"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-md border {{ !$invoices->hasMorePages() ? 'border-gray-200 bg-gray-100 text-gray-400 pointer-events-none' : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </nav>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/light.css">
    <style>
        .flatpickr-calendar {
            font-family: 'Inter', system-ui, sans-serif;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 8px;
        }
        .flatpickr-day.selected, .flatpickr-day.selected:hover {
            background: #2563eb;
            border-color: #2563eb;
        }
    </style>
    @endpush

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/mk.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr('[data-datepicker]', {
                dateFormat: 'd.m.Y',
                locale: 'mk',
                allowInput: true,
                disableMobile: true
            });
        });
    </script>
    @endpush
</x-app-layout>

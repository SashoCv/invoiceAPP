<x-app-layout>
    <div>
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('proforma.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('proforma.subtitle') }}</p>
            </div>
            @if(!$showDeleted)
                <a href="{{ route('proforma-invoices.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('proforma.new_proforma') }}
                </a>
            @endif
        </div>

        <!-- Tabs: Active / Deleted -->
        <div class="flex gap-4 mb-6 border-b border-gray-200">
            <a href="{{ route('proforma-invoices.index') }}"
               class="pb-3 px-1 text-sm font-medium border-b-2 transition-colors {{ !$showDeleted ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ __('proforma.active_proformas') }}
            </a>
            <a href="{{ route('proforma-invoices.index', ['deleted' => 1]) }}"
               class="pb-3 px-1 text-sm font-medium border-b-2 transition-colors {{ $showDeleted ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ __('proforma.deleted_proformas') }}
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
            <form method="GET" action="{{ route('proforma-invoices.index') }}" id="filterForm">
                <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                @if($showDeleted)
                    <input type="hidden" name="deleted" value="1">
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('proforma.search') }}</label>
                        <input type="text" name="proforma" value="{{ request('proforma') }}"
                               placeholder="{{ __('proforma.search_placeholder') }}"
                               class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Client -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('proforma.client') }}</label>
                        <select name="client"
                                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">{{ __('proforma.all_clients') }}</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ request('client') == $client->id ? 'selected' : '' }}>
                                    {{ $client->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('proforma.date_from') }}</label>
                        <input type="text" name="date_from"
                               value="{{ request('date_from') }}"
                               placeholder="дд.мм.гггг"
                               data-datepicker
                               readonly
                               class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white cursor-pointer">
                    </div>

                    <!-- Date To -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('proforma.date_to') }}</label>
                        <input type="text" name="date_to"
                               value="{{ request('date_to') }}"
                               placeholder="дд.мм.гггг"
                               data-datepicker
                               readonly
                               class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white cursor-pointer">
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('proforma.status') }}</label>
                        <select name="status"
                                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">{{ __('proforma.all_statuses') }}</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>{{ __('proforma.status_draft') }}</option>
                            <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>{{ __('proforma.status_sent') }}</option>
                            <option value="converted_to_invoice" {{ request('status') === 'converted_to_invoice' ? 'selected' : '' }}>{{ __('proforma.status_converted') }}</option>
                        </select>
                    </div>

                    <!-- Actions -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">&nbsp;</label>
                        <div class="flex gap-2">
                            <button type="submit" class="px-5 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                                {{ __('proforma.filter') }}
                            </button>
                            @if(request()->hasAny(['proforma', 'client', 'date_from', 'date_to', 'status']))
                                <a href="{{ route('proforma-invoices.index', $showDeleted ? ['deleted' => 1] : []) }}"
                                   class="px-4 py-2.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    {{ __('proforma.clear_filters') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Proforma List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            @if($proformas->isEmpty())
                <div class="text-center py-12">
                    @if($showDeleted)
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <p class="mt-4 text-gray-500">{{ __('proforma.no_deleted_proformas') }}</p>
                    @else
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="mt-4 text-gray-500">{{ __('proforma.no_proformas') }}</p>
                        <a href="{{ route('proforma-invoices.create') }}" class="mt-4 inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                            {{ __('proforma.create_first') }}
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
                                    {{ __('proforma.proforma') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                                    {{ __('proforma.client') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                                    {{ __('proforma.date') }}
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('proforma.status') }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('proforma.total') }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('proforma.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($proformas as $proforma)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <a href="{{ route('proforma-invoices.show', $proforma) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                            {{ $proforma->formatted_number }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 hidden md:table-cell">
                                        <span class="text-sm text-gray-900">{{ $proforma->client?->display_name ?? '-' }}</span>
                                    </td>
                                    <td class="px-6 py-4 hidden lg:table-cell">
                                        <span class="text-sm text-gray-600">{{ $proforma->issue_date->format('d.m.Y') }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @php
                                            $statusColors = [
                                                'draft' => 'bg-gray-100 text-gray-700',
                                                'sent' => 'bg-blue-100 text-blue-700',
                                                'converted_to_invoice' => 'bg-green-100 text-green-700',
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$proforma->status] ?? 'bg-gray-100 text-gray-700' }}">
                                            {{ __('proforma.status_' . $proforma->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="text-sm font-medium text-gray-900">{{ number_format($proforma->total, 2) }} {{ $proforma->currency_symbol }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right" x-data="{ deleteModal: false }">
                                        @if($showDeleted)
                                            <div class="flex items-center justify-end gap-1">
                                                <form action="{{ route('proforma-invoices.restore', $proforma->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                            title="{{ __('proforma.restore') }}"
                                                            class="p-2 text-gray-400 hover:text-green-600 rounded-lg hover:bg-green-50 transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                                <button type="button" @click="deleteModal = true"
                                                        title="{{ __('proforma.delete_permanently') }}"
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
                                                        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('proforma.delete_permanently') }}</h3>
                                                        <p class="text-sm text-gray-600 mb-2">{{ $proforma->formatted_number }}</p>
                                                        <p class="text-sm text-gray-600 mb-6">{{ __('proforma.delete_permanently_confirm') }}</p>
                                                        <div class="flex gap-3">
                                                            <button type="button" @click="deleteModal = false"
                                                                    class="flex-1 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                                                                {{ __('proforma.cancel') }}
                                                            </button>
                                                            <form action="{{ route('proforma-invoices.force-delete', $proforma->id) }}" method="POST" class="flex-1">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                        class="w-full px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                                                                    {{ __('proforma.delete') }}
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="flex items-center justify-end gap-1">
                                                <a href="{{ route('proforma-invoices.show', $proforma) }}"
                                                   title="{{ __('proforma.view') }}"
                                                   class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                </a>
                                                <a href="{{ route('proforma-invoices.duplicate', $proforma) }}"
                                                   title="{{ __('proforma.duplicate') }}"
                                                   class="p-2 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                    </svg>
                                                </a>
                                                <a href="{{ route('proforma-invoices.edit', $proforma) }}"
                                                   title="{{ __('proforma.edit_proforma') }}"
                                                   class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </a>
                                                <button type="button" @click="deleteModal = true"
                                                        title="{{ __('proforma.delete') }}"
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
                                                        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('proforma.delete_proforma') }}</h3>
                                                        <p class="text-sm text-gray-600 mb-2">{{ $proforma->formatted_number }}</p>
                                                        <p class="text-sm text-gray-600 mb-6">{{ __('proforma.delete_confirm') }}</p>
                                                        <div class="flex gap-3">
                                                            <button type="button" @click="deleteModal = false"
                                                                    class="flex-1 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                                                                {{ __('proforma.cancel') }}
                                                            </button>
                                                            <form action="{{ route('proforma-invoices.destroy', $proforma) }}" method="POST" class="flex-1">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                        class="w-full px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                                                                    {{ __('proforma.delete') }}
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
                        <p class="text-sm text-gray-600">
                            {{ __('proforma.showing') }}
                            <span class="font-semibold text-gray-900">{{ $proformas->firstItem() ?? 0 }}</span>
                            -
                            <span class="font-semibold text-gray-900">{{ $proformas->lastItem() ?? 0 }}</span>
                            {{ __('proforma.of') }}
                            <span class="font-semibold text-gray-900">{{ $proformas->total() }}</span>
                        </p>

                        <div class="flex items-center gap-6">
                            <div class="hidden sm:flex items-center gap-2 text-sm text-gray-600">
                                <span>{{ __('proforma.per_page') }}:</span>
                                <select onchange="window.location.href='{{ route('proforma-invoices.index') }}?' + new URLSearchParams({...Object.fromEntries(new URLSearchParams(window.location.search)), per_page: this.value, page: 1}).toString()"
                                        class="border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                                    @foreach([10, 25, 50, 100] as $num)
                                        <option value="{{ $num }}" {{ request('per_page', 10) == $num ? 'selected' : '' }}>{{ $num }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @if($proformas->hasPages())
                                <nav class="flex items-center gap-1">
                                    <a href="{{ $proformas->onFirstPage() ? '#' : $proformas->previousPageUrl() }}"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-md border {{ $proformas->onFirstPage() ? 'border-gray-200 bg-gray-100 text-gray-400 pointer-events-none' : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                        </svg>
                                    </a>

                                    @if($proformas->currentPage() > 3)
                                        <a href="{{ $proformas->url(1) }}" class="inline-flex items-center justify-center w-8 h-8 text-sm rounded-md border border-gray-300 bg-white text-gray-600 hover:bg-gray-50">1</a>
                                        @if($proformas->currentPage() > 4)
                                            <span class="w-8 text-center text-gray-400">...</span>
                                        @endif
                                    @endif

                                    @foreach(range(max(1, $proformas->currentPage() - 1), min($proformas->lastPage(), $proformas->currentPage() + 1)) as $page)
                                        <a href="{{ $proformas->url($page) }}"
                                           class="inline-flex items-center justify-center w-8 h-8 text-sm rounded-md border {{ $page == $proformas->currentPage() ? 'border-blue-600 bg-blue-600 text-white font-medium' : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50' }}">
                                            {{ $page }}
                                        </a>
                                    @endforeach

                                    @if($proformas->currentPage() < $proformas->lastPage() - 2)
                                        @if($proformas->currentPage() < $proformas->lastPage() - 3)
                                            <span class="w-8 text-center text-gray-400">...</span>
                                        @endif
                                        <a href="{{ $proformas->url($proformas->lastPage()) }}" class="inline-flex items-center justify-center w-8 h-8 text-sm rounded-md border border-gray-300 bg-white text-gray-600 hover:bg-gray-50">{{ $proformas->lastPage() }}</a>
                                    @endif

                                    <a href="{{ $proformas->hasMorePages() ? $proformas->nextPageUrl() : '#' }}"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-md border {{ !$proformas->hasMorePages() ? 'border-gray-200 bg-gray-100 text-gray-400 pointer-events-none' : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
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

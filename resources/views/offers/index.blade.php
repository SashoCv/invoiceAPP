<x-app-layout>
    <div>
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('offers.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('offers.subtitle') }}</p>
            </div>
            @if(!$showDeleted)
                <a href="{{ route('offers.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('offers.new_offer') }}
                </a>
            @endif
        </div>

        <!-- Tabs: Active / Deleted -->
        <div class="flex gap-4 mb-6 border-b border-gray-200">
            <a href="{{ route('offers.index') }}"
               class="pb-3 px-1 text-sm font-medium border-b-2 transition-colors {{ !$showDeleted ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ __('offers.active_offers') }}
            </a>
            <a href="{{ route('offers.index', ['deleted' => 1]) }}"
               class="pb-3 px-1 text-sm font-medium border-b-2 transition-colors {{ $showDeleted ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                {{ __('offers.deleted_offers') }}
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
            <form method="GET" action="{{ route('offers.index') }}" id="filterForm">
                <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                @if($showDeleted)
                    <input type="hidden" name="deleted" value="1">
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('offers.search') }}</label>
                        <input type="text" name="offer" value="{{ request('offer') }}"
                               placeholder="{{ __('offers.search_placeholder') }}"
                               class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Client -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('offers.client') }}</label>
                        <select name="client"
                                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">{{ __('offers.all_clients') }}</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ request('client') == $client->id ? 'selected' : '' }}>
                                    {{ $client->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('offers.status') }}</label>
                        <select name="status"
                                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">{{ __('offers.all_statuses') }}</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>{{ __('offers.status_draft') }}</option>
                            <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>{{ __('offers.status_sent') }}</option>
                            <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>{{ __('offers.status_accepted') }}</option>
                            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>{{ __('offers.status_rejected') }}</option>
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('offers.date_from') }}</label>
                        <input type="text" name="date_from"
                               value="{{ request('date_from') }}"
                               placeholder="дд.мм.гггг"
                               data-datepicker
                               readonly
                               class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white cursor-pointer">
                    </div>

                    <!-- Date To -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('offers.date_to') }}</label>
                        <input type="text" name="date_to"
                               value="{{ request('date_to') }}"
                               placeholder="дд.мм.гггг"
                               data-datepicker
                               readonly
                               class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white cursor-pointer">
                    </div>

                    <!-- Actions -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">&nbsp;</label>
                        <div class="flex gap-2">
                            <button type="submit" class="px-5 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                                {{ __('offers.filter') }}
                            </button>
                            @if(request()->hasAny(['offer', 'client', 'date_from', 'date_to', 'status']))
                                <a href="{{ route('offers.index', $showDeleted ? ['deleted' => 1] : []) }}"
                                   class="px-4 py-2.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    {{ __('offers.clear_filters') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Offers List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            @if($offers->isEmpty())
                <div class="text-center py-12">
                    @if($showDeleted)
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <p class="mt-4 text-gray-500">{{ __('offers.no_deleted_offers') }}</p>
                    @else
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="mt-4 text-gray-500">{{ __('offers.no_offers') }}</p>
                        <a href="{{ route('offers.create') }}" class="mt-4 inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                            {{ __('offers.create_first') }}
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
                                    {{ __('offers.offer') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('offers.offer_title') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                                    {{ __('offers.client') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                                    {{ __('offers.date') }}
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('offers.status') }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('offers.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($offers as $offer)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <a href="{{ route('offers.show', $offer) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                            {{ $offer->formatted_number }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-gray-900">{{ Str::limit($offer->title, 40) }}</span>
                                    </td>
                                    <td class="px-6 py-4 hidden md:table-cell">
                                        <span class="text-sm text-gray-900">{{ $offer->client?->display_name ?? '-' }}</span>
                                    </td>
                                    <td class="px-6 py-4 hidden lg:table-cell">
                                        <span class="text-sm text-gray-600">{{ $offer->issue_date->format('d.m.Y') }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @php
                                            $statusColors = [
                                                'draft' => 'bg-gray-100 text-gray-700',
                                                'sent' => 'bg-blue-100 text-blue-700',
                                                'accepted' => 'bg-green-100 text-green-700',
                                                'rejected' => 'bg-red-100 text-red-700',
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$offer->status] ?? 'bg-gray-100 text-gray-700' }}">
                                            {{ __('offers.status_' . $offer->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right" x-data="{ deleteModal: false }">
                                        @if($showDeleted)
                                            <div class="flex items-center justify-end gap-1">
                                                <form action="{{ route('offers.restore', $offer->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                            title="{{ __('offers.restore') }}"
                                                            class="p-2 text-gray-400 hover:text-green-600 rounded-lg hover:bg-green-50 transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                                <button type="button" @click="deleteModal = true"
                                                        title="{{ __('offers.delete_permanently') }}"
                                                        class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Force Delete Modal -->
                                            <div x-show="deleteModal" x-cloak
                                                 class="fixed inset-0 z-[100] overflow-y-auto"
                                                 x-transition>
                                                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
                                                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="deleteModal = false"></div>

                                                    <div class="relative bg-white rounded-xl shadow-xl w-80 p-6 z-10">
                                                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 mb-4">
                                                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                            </svg>
                                                        </div>
                                                        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('offers.delete_permanently') }}</h3>
                                                        <p class="text-sm text-gray-600 mb-6">{{ __('offers.delete_permanently_confirm') }}</p>
                                                        <div class="flex gap-3">
                                                            <button type="button" @click="deleteModal = false"
                                                                    class="flex-1 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                                                                {{ __('offers.cancel') }}
                                                            </button>
                                                            <form action="{{ route('offers.force-delete', $offer->id) }}" method="POST" class="flex-1">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                        class="w-full px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                                                                    {{ __('offers.delete') }}
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="flex items-center justify-end gap-1">
                                                <a href="{{ route('offers.show', $offer) }}"
                                                   title="{{ __('offers.view') }}"
                                                   class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                </a>
                                                <a href="{{ route('offers.duplicate', $offer) }}"
                                                   title="{{ __('offers.duplicate') }}"
                                                   class="p-2 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                    </svg>
                                                </a>
                                                <a href="{{ route('offers.edit', $offer) }}"
                                                   title="{{ __('offers.edit_offer') }}"
                                                   class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </a>
                                                <button type="button" @click="deleteModal = true"
                                                        title="{{ __('offers.delete') }}"
                                                        class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </div>

                                            <!-- Delete Modal -->
                                            <div x-show="deleteModal" x-cloak
                                                 class="fixed inset-0 z-[100] overflow-y-auto"
                                                 x-transition>
                                                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
                                                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="deleteModal = false"></div>

                                                    <div class="relative bg-white rounded-xl shadow-xl w-80 p-6 z-10">
                                                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 mb-4">
                                                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                            </svg>
                                                        </div>
                                                        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('offers.delete_offer') }}</h3>
                                                        <p class="text-sm text-gray-600 mb-6">{{ __('offers.delete_confirm') }}</p>
                                                        <div class="flex gap-3">
                                                            <button type="button" @click="deleteModal = false"
                                                                    class="flex-1 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                                                                {{ __('offers.cancel') }}
                                                            </button>
                                                            <form action="{{ route('offers.destroy', $offer) }}" method="POST" class="flex-1">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                        class="w-full px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                                                                    {{ __('offers.delete') }}
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
                            {{ __('offers.showing') }}
                            <span class="font-semibold text-gray-900">{{ $offers->firstItem() ?? 0 }}</span>
                            -
                            <span class="font-semibold text-gray-900">{{ $offers->lastItem() ?? 0 }}</span>
                            {{ __('offers.of') }}
                            <span class="font-semibold text-gray-900">{{ $offers->total() }}</span>
                        </p>

                        <div class="flex items-center gap-6">
                            <div class="hidden sm:flex items-center gap-2 text-sm text-gray-600">
                                <span>{{ __('offers.per_page') }}:</span>
                                <select onchange="window.location.href='{{ route('offers.index') }}?' + new URLSearchParams({...Object.fromEntries(new URLSearchParams(window.location.search)), per_page: this.value, page: 1}).toString()"
                                        class="border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                                    @foreach([10, 25, 50, 100] as $num)
                                        <option value="{{ $num }}" {{ request('per_page', 10) == $num ? 'selected' : '' }}>{{ $num }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @if($offers->hasPages())
                                <nav class="flex items-center gap-1">
                                    <a href="{{ $offers->onFirstPage() ? '#' : $offers->previousPageUrl() }}"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-md border {{ $offers->onFirstPage() ? 'border-gray-200 bg-gray-100 text-gray-400 pointer-events-none' : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                        </svg>
                                    </a>

                                    @foreach(range(max(1, $offers->currentPage() - 1), min($offers->lastPage(), $offers->currentPage() + 1)) as $page)
                                        <a href="{{ $offers->url($page) }}"
                                           class="inline-flex items-center justify-center w-8 h-8 text-sm rounded-md border {{ $page == $offers->currentPage() ? 'border-blue-600 bg-blue-600 text-white font-medium' : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50' }}">
                                            {{ $page }}
                                        </a>
                                    @endforeach

                                    <a href="{{ $offers->hasMorePages() ? $offers->nextPageUrl() : '#' }}"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-md border {{ !$offers->hasMorePages() ? 'border-gray-200 bg-gray-100 text-gray-400 pointer-events-none' : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
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

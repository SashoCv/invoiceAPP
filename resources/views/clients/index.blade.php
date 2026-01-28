<x-app-layout>
    <div>
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('clients.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('clients.subtitle') }}</p>
            </div>
            <div class="flex items-center gap-3">
                @if($archivedCount > 0)
                    <a href="{{ route('clients.archived') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-gray-600 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                        {{ __('clients.archived') }}
                        <span class="bg-gray-200 text-gray-700 px-1.5 py-0.5 rounded-full text-xs">{{ $archivedCount }}</span>
                    </a>
                @endif
                <a href="{{ route('clients.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('clients.add_client') }}
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
            <form method="GET" action="{{ route('clients.index') }}">
                <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('clients.search') }}</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="{{ __('clients.search_placeholder') }}"
                               class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- City -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('clients.city') }}</label>
                        <select name="city"
                                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">{{ __('clients.all_cities') }}</option>
                            @foreach($cities as $city)
                                <option value="{{ $city }}" {{ request('city') === $city ? 'selected' : '' }}>{{ $city }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Actions -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">&nbsp;</label>
                        <div class="flex gap-2">
                            <button type="submit" class="px-5 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                                {{ __('clients.filter') }}
                            </button>
                            @if(request()->hasAny(['search', 'city']))
                                <a href="{{ route('clients.index') }}"
                                   class="px-4 py-2.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    {{ __('clients.clear_filters') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Clients List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            @if($clients->isEmpty())
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <p class="mt-4 text-gray-500">{{ __('clients.no_clients') }}</p>
                    <a href="{{ route('clients.create') }}" class="mt-4 inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                        {{ __('clients.add_first_client') }}
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('clients.client') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                                    {{ __('clients.contact') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                                    {{ __('clients.tax_number') }}
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                                    {{ __('clients.invoices_count') }}
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                                    {{ __('clients.proformas_count') }}
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                                    {{ __('clients.contracts_count') }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('clients.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($clients as $client)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ $client->company ?: $client->name }}
                                            </p>
                                            @if($client->company)
                                                <p class="text-xs text-gray-500">{{ $client->name }}</p>
                                            @endif
                                            @if($client->city)
                                                <p class="text-xs text-gray-400">{{ $client->city }}, {{ $client->country }}</p>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 hidden md:table-cell">
                                        <div class="text-sm text-gray-600">
                                            @if($client->email)
                                                <p>{{ $client->email }}</p>
                                            @endif
                                            @if($client->phone)
                                                <p class="text-gray-400">{{ $client->phone }}</p>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 hidden lg:table-cell">
                                        <span class="text-sm text-gray-600">{{ $client->tax_number ?: '-' }}</span>
                                    </td>
                                    <td class="px-6 py-4 hidden lg:table-cell text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $client->invoices_count > 0 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $client->invoices_count }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 hidden lg:table-cell text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $client->proforma_invoices_count > 0 ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $client->proforma_invoices_count }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 hidden lg:table-cell text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $client->contracts_count > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $client->contracts_count }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('clients.edit', $client) }}"
                                               class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($clients->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-gray-600">
                                {{ __('clients.showing') }}
                                <span class="font-semibold text-gray-900">{{ $clients->firstItem() ?? 0 }}</span>
                                -
                                <span class="font-semibold text-gray-900">{{ $clients->lastItem() ?? 0 }}</span>
                                {{ __('clients.of') }}
                                <span class="font-semibold text-gray-900">{{ $clients->total() }}</span>
                            </p>

                            <div class="flex items-center gap-6">
                                <div class="hidden sm:flex items-center gap-2 text-sm text-gray-600">
                                    <span>{{ __('clients.per_page') }}:</span>
                                    <select onchange="window.location.href='{{ route('clients.index') }}?' + new URLSearchParams({...Object.fromEntries(new URLSearchParams(window.location.search)), per_page: this.value, page: 1}).toString()"
                                            class="border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                                        @foreach([10, 25, 50, 100] as $num)
                                            <option value="{{ $num }}" {{ request('per_page', 10) == $num ? 'selected' : '' }}>{{ $num }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <nav class="flex items-center gap-1">
                                    <a href="{{ $clients->onFirstPage() ? '#' : $clients->previousPageUrl() }}"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-md border {{ $clients->onFirstPage() ? 'border-gray-200 bg-gray-100 text-gray-400 pointer-events-none' : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                        </svg>
                                    </a>

                                    @foreach(range(max(1, $clients->currentPage() - 1), min($clients->lastPage(), $clients->currentPage() + 1)) as $page)
                                        <a href="{{ $clients->url($page) }}"
                                           class="inline-flex items-center justify-center w-8 h-8 text-sm rounded-md border {{ $page == $clients->currentPage() ? 'border-blue-600 bg-blue-600 text-white font-medium' : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50' }}">
                                            {{ $page }}
                                        </a>
                                    @endforeach

                                    <a href="{{ $clients->hasMorePages() ? $clients->nextPageUrl() : '#' }}"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-md border {{ !$clients->hasMorePages() ? 'border-gray-200 bg-gray-100 text-gray-400 pointer-events-none' : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </nav>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <div>
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('articles.title') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('articles.subtitle') }}</p>
            </div>
            <a href="{{ route('articles.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('articles.add_article') }}
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
            <form method="GET" action="{{ route('articles.index') }}">
                <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('articles.search') }}</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="{{ __('articles.search_placeholder') }}"
                               class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('articles.status') }}</label>
                        <select name="status"
                                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">{{ __('articles.all_statuses') }}</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('articles.status_active') }}</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('articles.status_inactive') }}</option>
                        </select>
                    </div>

                    <!-- Actions -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">&nbsp;</label>
                        <div class="flex gap-2">
                            <button type="submit" class="px-5 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                                {{ __('articles.filter') }}
                            </button>
                            @if(request()->hasAny(['search', 'status']))
                                <a href="{{ route('articles.index') }}"
                                   class="px-4 py-2.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    {{ __('articles.clear_filters') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Articles List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            @if($articles->isEmpty())
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <p class="mt-4 text-gray-500">{{ __('articles.no_articles') }}</p>
                    <a href="{{ route('articles.create') }}" class="mt-4 inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                        {{ __('articles.add_first_article') }}
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
                                    {{ __('articles.article') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                                    {{ __('articles.unit') }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('articles.price') }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                                    {{ __('articles.tax') }}
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                                    {{ __('articles.status') }}
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('articles.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($articles as $article)
                                <tr class="hover:bg-gray-50 {{ !$article->is_active ? 'opacity-50' : '' }}">
                                    <td class="px-6 py-4">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $article->name }}</p>
                                            @if($article->description)
                                                <p class="text-xs text-gray-500 truncate max-w-xs">{{ $article->description }}</p>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 hidden md:table-cell">
                                        <span class="text-sm text-gray-600">{{ $article->unit }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="text-sm font-medium text-gray-900">{{ number_format($article->price, 2) }} ден.</span>
                                    </td>
                                    <td class="px-6 py-4 text-right hidden md:table-cell">
                                        <span class="text-sm text-gray-600">{{ number_format($article->tax_rate, 0) }}%</span>
                                    </td>
                                    <td class="px-6 py-4 text-center hidden sm:table-cell">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $article->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $article->is_active ? __('articles.status_active') : __('articles.status_inactive') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('articles.edit', $article) }}"
                                           class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors inline-block">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($articles->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-gray-600">
                                {{ __('articles.showing') }}
                                <span class="font-semibold text-gray-900">{{ $articles->firstItem() ?? 0 }}</span>
                                -
                                <span class="font-semibold text-gray-900">{{ $articles->lastItem() ?? 0 }}</span>
                                {{ __('articles.of') }}
                                <span class="font-semibold text-gray-900">{{ $articles->total() }}</span>
                            </p>

                            <div class="flex items-center gap-6">
                                <div class="hidden sm:flex items-center gap-2 text-sm text-gray-600">
                                    <span>{{ __('articles.per_page') }}:</span>
                                    <select onchange="window.location.href='{{ route('articles.index') }}?' + new URLSearchParams({...Object.fromEntries(new URLSearchParams(window.location.search)), per_page: this.value, page: 1}).toString()"
                                            class="border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                                        @foreach([10, 25, 50, 100] as $num)
                                            <option value="{{ $num }}" {{ request('per_page', 10) == $num ? 'selected' : '' }}>{{ $num }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <nav class="flex items-center gap-1">
                                    <a href="{{ $articles->onFirstPage() ? '#' : $articles->previousPageUrl() }}"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-md border {{ $articles->onFirstPage() ? 'border-gray-200 bg-gray-100 text-gray-400 pointer-events-none' : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                        </svg>
                                    </a>

                                    @foreach(range(max(1, $articles->currentPage() - 1), min($articles->lastPage(), $articles->currentPage() + 1)) as $page)
                                        <a href="{{ $articles->url($page) }}"
                                           class="inline-flex items-center justify-center w-8 h-8 text-sm rounded-md border {{ $page == $articles->currentPage() ? 'border-blue-600 bg-blue-600 text-white font-medium' : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50' }}">
                                            {{ $page }}
                                        </a>
                                    @endforeach

                                    <a href="{{ $articles->hasMorePages() ? $articles->nextPageUrl() : '#' }}"
                                       class="inline-flex items-center justify-center w-8 h-8 rounded-md border {{ !$articles->hasMorePages() ? 'border-gray-200 bg-gray-100 text-gray-400 pointer-events-none' : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
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

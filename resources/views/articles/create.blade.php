<x-app-layout>
    <div>
        <!-- Page Header -->
        <div class="mb-6">
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('articles.index') }}" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('articles.add_article') }}</h1>
            </div>
            <p class="text-sm text-gray-500">{{ __('articles.add_article_desc') }}</p>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 max-w-2xl">
            <form method="POST" action="{{ route('articles.store') }}">
                @csrf

                @include('articles._form', ['article' => null])

                <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-gray-200">
                    <a href="{{ route('articles.index') }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        {{ __('articles.cancel') }}
                    </a>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800 transition-colors">
                        {{ __('articles.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

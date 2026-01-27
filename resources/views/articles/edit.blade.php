<x-app-layout>
    <div x-data="{ deleteModal: false }">
        <!-- Page Header -->
        <div class="mb-6">
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('articles.index') }}" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('articles.edit_article') }}</h1>
            </div>
            <p class="text-sm text-gray-500">{{ $article->name }}</p>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 max-w-2xl">
            <form method="POST" action="{{ route('articles.update', $article) }}">
                @csrf
                @method('PUT')

                @include('articles._form', ['article' => $article])

                <div class="flex items-center justify-between mt-6 pt-6 border-t border-gray-200">
                    <button type="button" @click="deleteModal = true"
                            class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-700 transition-colors">
                        {{ __('articles.delete_article') }}
                    </button>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('articles.index') }}"
                           class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            {{ __('articles.cancel') }}
                        </a>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800 transition-colors">
                            {{ __('articles.save') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Delete Modal -->
        <div x-show="deleteModal" x-cloak
             class="fixed inset-0 z-[100] overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50 transition-opacity" @click="deleteModal = false"></div>
                <div class="relative bg-white rounded-xl shadow-xl w-80 p-6 z-10">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">{{ __('articles.delete_article') }}</h3>
                    <p class="text-sm text-gray-500 text-center mb-6">
                        {{ __('articles.delete_confirm') }}
                    </p>
                    <div class="flex gap-3">
                        <button type="button" @click="deleteModal = false"
                                class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            {{ __('articles.cancel') }}
                        </button>
                        <form method="POST" action="{{ route('articles.destroy', $article) }}" class="flex-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                                {{ __('articles.delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

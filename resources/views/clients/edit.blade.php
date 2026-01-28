<x-app-layout>
    <div x-data="{ deleteModal: false }">
        <!-- Page Header -->
        <div class="mb-6">
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('clients.index') }}" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('clients.edit_client') }}</h1>
            </div>
            <p class="text-sm text-gray-500">{{ $client->display_name }}</p>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <form method="POST" action="{{ route('clients.update', $client) }}">
                @csrf
                @method('PUT')

                @include('clients._form', ['client' => $client])

                <div class="flex items-center justify-between mt-6 pt-6 border-t border-gray-200">
                    <button type="button" @click="deleteModal = true"
                            class="px-4 py-2 text-sm font-medium text-amber-600 hover:text-amber-700 transition-colors">
                        {{ __('clients.archive_client') }}
                    </button>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('clients.index') }}"
                           class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            {{ __('clients.cancel') }}
                        </a>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800 transition-colors">
                            {{ __('clients.save') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Contracts Section -->
        <div class="mt-6">
            @include('clients._contracts', ['client' => $client, 'contracts' => $contracts])
        </div>

        <!-- Archive Modal -->
        <div x-show="deleteModal" x-cloak
             class="fixed inset-0 z-[100] overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50 transition-opacity" @click="deleteModal = false"></div>
                <div class="relative bg-white rounded-xl shadow-xl w-80 p-6 z-10">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-amber-100 rounded-full mb-4">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">{{ __('clients.archive_client') }}</h3>
                    <p class="text-sm text-gray-500 text-center mb-6">
                        {{ __('clients.archive_confirm') }}
                    </p>
                    <div class="flex gap-3">
                        <button type="button" @click="deleteModal = false"
                                class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            {{ __('clients.cancel') }}
                        </button>
                        <form method="POST" action="{{ route('clients.destroy', $client) }}" class="flex-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full px-4 py-2 text-sm font-medium text-white bg-amber-500 rounded-lg hover:bg-amber-600 transition-colors">
                                {{ __('clients.archive') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

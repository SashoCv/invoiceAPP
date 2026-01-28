<div x-data="{ uploadModal: false, deleteModal: false, deleteId: null }">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ __('contracts.title') }}</h2>
                <p class="text-sm text-gray-500">{{ __('contracts.subtitle') }}</p>
            </div>
            <button type="button" @click="uploadModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('contracts.upload') }}
            </button>
        </div>

        @if($contracts->isEmpty())
            <div class="text-center py-8">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm text-gray-500">{{ __('contracts.no_contracts') }}</p>
            </div>
        @else
            <div class="overflow-hidden border border-gray-200 rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('contracts.name') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('contracts.size') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('contracts.uploaded') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('contracts.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($contracts as $contract)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @php
                                            $iconColors = [
                                                'pdf' => 'text-red-500',
                                                'doc' => 'text-blue-500',
                                                'image' => 'text-green-500',
                                                'file' => 'text-gray-500',
                                            ];
                                            $iconColor = $iconColors[$contract->icon] ?? 'text-gray-500';
                                        @endphp
                                        <div class="{{ $iconColor }}">
                                            @if($contract->icon === 'pdf')
                                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M13,9V3.5L18.5,9H13Z"/>
                                                </svg>
                                            @elseif($contract->icon === 'doc')
                                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M15.8,20H14.6L12,13.5L9.4,20H8.2L6,15H7.1L8.8,19L11.4,12H12.6L15.2,19L16.9,15H18L15.8,20M13,9V3.5L18.5,9H13Z"/>
                                                </svg>
                                            @elseif($contract->icon === 'image')
                                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M8.5,13.5L11,16.5L14.5,12L19,18H5M21,19V5C21,3.89 20.1,3 19,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19Z"/>
                                                </svg>
                                            @else
                                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M13,9V3.5L18.5,9H13Z"/>
                                                </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $contract->title }}</p>
                                            <p class="text-xs text-gray-500">{{ $contract->original_name }}</p>
                                            @if($contract->description)
                                                <p class="text-xs text-gray-400 mt-0.5">{{ Str::limit($contract->description, 50) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $contract->formatted_size }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $contract->uploaded_at->format('d.m.Y') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('contracts.download', $contract) }}"
                                           class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                           title="{{ __('contracts.download') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                        </a>
                                        <button type="button" @click="deleteId = {{ $contract->id }}; deleteModal = true"
                                                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                title="{{ __('contracts.delete') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Upload Modal -->
    <div x-show="uploadModal" x-cloak class="fixed inset-0 z-[100] overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="uploadModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6 z-10">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('contracts.upload_new') }}</h3>
                <form action="{{ route('clients.contracts.store', $client) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('contracts.file') }} <span class="text-red-500">*</span></label>
                            <input type="file" name="file" required
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <p class="mt-1 text-xs text-gray-500">{{ __('contracts.allowed_types') }}</p>
                            <p class="text-xs text-gray-500">{{ __('contracts.max_size') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('contracts.file_title') }}</label>
                            <input type="text" name="title"
                                   placeholder="{{ __('contracts.file_title_placeholder') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('contracts.description') }}</label>
                            <textarea name="description" rows="2"
                                      placeholder="{{ __('contracts.description_placeholder') }}"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"></textarea>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-6">
                        <button type="button" @click="uploadModal = false"
                                class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            {{ __('contracts.cancel') }}
                        </button>
                        <button type="submit"
                                class="flex-1 px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800 transition-colors">
                            {{ __('contracts.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div x-show="deleteModal" x-cloak class="fixed inset-0 z-[100] overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="deleteModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl w-80 p-6 z-10">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">{{ __('contracts.delete') }}</h3>
                <p class="text-sm text-gray-500 text-center mb-6">{{ __('contracts.delete_confirm') }}</p>
                <div class="flex gap-3">
                    <button type="button" @click="deleteModal = false"
                            class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        {{ __('contracts.cancel') }}
                    </button>
                    <form :action="'/contracts/' + deleteId" method="POST" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                            {{ __('contracts.delete') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

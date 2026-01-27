<x-settings-layout>
    <div class="p-6">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900">{{ __('settings.templates_title') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ __('settings.templates_desc') }}</p>
        </div>

        <form method="POST" action="{{ route('settings.templates.update') }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($templates as $key => $template)
                    <label class="relative cursor-pointer group">
                        <input type="radio" name="template" value="{{ $key }}" class="sr-only peer"
                               {{ $currentTemplate === $key ? 'checked' : '' }}>

                        <div class="border-2 rounded-xl p-4 transition-all
                                    peer-checked:border-blue-500 peer-checked:bg-blue-50
                                    hover:border-gray-300 border-gray-200">

                            <!-- Template Preview -->
                            <div class="aspect-[3/4] bg-white border border-gray-200 rounded-lg mb-3 overflow-hidden shadow-sm">
                                @if($key === 'classic')
                                    <div class="p-3 h-full flex flex-col">
                                        <div class="flex justify-between items-start mb-4">
                                            <div class="w-8 h-8 bg-gray-800 rounded"></div>
                                            <div class="text-right">
                                                <div class="w-12 h-2 bg-gray-800 rounded mb-1"></div>
                                                <div class="w-8 h-1.5 bg-gray-300 rounded"></div>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <div class="w-16 h-1.5 bg-gray-300 rounded mb-2"></div>
                                            <div class="w-12 h-1 bg-gray-200 rounded mb-1"></div>
                                            <div class="w-14 h-1 bg-gray-200 rounded mb-4"></div>
                                            <div class="border-t border-gray-200 pt-2">
                                                <div class="flex justify-between mb-1">
                                                    <div class="w-16 h-1 bg-gray-300 rounded"></div>
                                                    <div class="w-8 h-1 bg-gray-300 rounded"></div>
                                                </div>
                                                <div class="flex justify-between mb-1">
                                                    <div class="w-12 h-1 bg-gray-200 rounded"></div>
                                                    <div class="w-6 h-1 bg-gray-200 rounded"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="border-t border-gray-300 pt-2 mt-auto">
                                            <div class="flex justify-between">
                                                <div class="w-10 h-1.5 bg-gray-800 rounded"></div>
                                                <div class="w-12 h-1.5 bg-gray-800 rounded"></div>
                                            </div>
                                        </div>
                                    </div>
                                @elseif($key === 'modern')
                                    <div class="h-full flex flex-col">
                                        <div class="bg-blue-600 p-3">
                                            <div class="flex justify-between items-center">
                                                <div class="w-8 h-8 bg-white/20 rounded"></div>
                                                <div class="w-12 h-2 bg-white rounded"></div>
                                            </div>
                                        </div>
                                        <div class="p-3 flex-1 flex flex-col">
                                            <div class="w-16 h-1.5 bg-gray-300 rounded mb-2"></div>
                                            <div class="w-12 h-1 bg-gray-200 rounded mb-4"></div>
                                            <div class="bg-gray-50 rounded p-2 mb-2">
                                                <div class="flex justify-between mb-1">
                                                    <div class="w-14 h-1 bg-gray-300 rounded"></div>
                                                    <div class="w-6 h-1 bg-blue-400 rounded"></div>
                                                </div>
                                                <div class="flex justify-between">
                                                    <div class="w-10 h-1 bg-gray-200 rounded"></div>
                                                    <div class="w-5 h-1 bg-blue-300 rounded"></div>
                                                </div>
                                            </div>
                                            <div class="mt-auto pt-2 border-t">
                                                <div class="flex justify-end">
                                                    <div class="w-14 h-2 bg-blue-600 rounded"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @elseif($key === 'minimal')
                                    <div class="p-3 h-full flex flex-col">
                                        <div class="text-center mb-4 pt-2">
                                            <div class="w-6 h-6 bg-gray-200 rounded-full mx-auto mb-2"></div>
                                            <div class="w-14 h-1.5 bg-gray-300 rounded mx-auto"></div>
                                        </div>
                                        <div class="flex-1">
                                            <div class="w-10 h-1 bg-gray-200 rounded mb-3"></div>
                                            <div class="space-y-1 mb-4">
                                                <div class="flex justify-between">
                                                    <div class="w-20 h-1 bg-gray-200 rounded"></div>
                                                    <div class="w-6 h-1 bg-gray-300 rounded"></div>
                                                </div>
                                                <div class="flex justify-between">
                                                    <div class="w-16 h-1 bg-gray-100 rounded"></div>
                                                    <div class="w-5 h-1 bg-gray-200 rounded"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-center pt-2 border-t border-gray-100">
                                            <div class="w-12 h-1.5 bg-gray-400 rounded mx-auto"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Template Info -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900">{{ __('settings.template_' . $key) }}</p>
                                    <p class="text-xs text-gray-500">{{ __('settings.template_' . $key . '_desc') }}</p>
                                </div>
                                <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center
                                            peer-checked:border-blue-500 peer-checked:bg-blue-500
                                            border-gray-300">
                                    <svg class="w-3 h-3 text-white hidden peer-checked:block" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- Selected indicator -->
                            @if($currentTemplate === $key)
                                <div class="absolute top-2 right-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                        {{ __('settings.current') }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                        class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                    {{ __('settings.save') }}
                </button>
            </div>
        </form>
    </div>
</x-settings-layout>

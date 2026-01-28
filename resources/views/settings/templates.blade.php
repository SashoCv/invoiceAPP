<x-settings-layout>
    <div class="p-6">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900">{{ __('settings.templates_title') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ __('settings.templates_desc') }}</p>
        </div>

        <!-- Tab Navigation -->
        <div x-data="{ activeTab: 'invoices' }" class="mb-6">
            <div class="flex gap-2 border-b border-gray-200">
                <button @click="activeTab = 'invoices'"
                        :class="activeTab === 'invoices' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 -mb-px transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                    {{ __('settings.invoice_templates') }}
                </button>
                <button @click="activeTab = 'offers'"
                        :class="activeTab === 'offers' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 -mb-px transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                    </svg>
                    {{ __('settings.offer_templates') }}
                </button>
            </div>

            <form method="POST" action="{{ route('settings.templates.update') }}" class="mt-6">
                @csrf
                @method('PUT')

                <!-- Invoice Templates Tab -->
                <div x-show="activeTab === 'invoices'" x-transition>
                    <p class="text-sm text-gray-500 mb-4">{{ __('settings.invoice_templates_desc') }}</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach($templates as $key => $template)
                            <label class="relative cursor-pointer group">
                                <input type="radio" name="invoice_template" value="{{ $key }}" class="sr-only peer"
                                       {{ $currentInvoiceTemplate === $key ? 'checked' : '' }}>
                                <div class="border-2 rounded-xl p-4 transition-all
                                            peer-checked:border-blue-500 peer-checked:bg-blue-50
                                            hover:border-gray-300 border-gray-200">
                                    @include('settings._template_preview', ['key' => $key])
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ __('settings.template_' . $key) }}</p>
                                            <p class="text-xs text-gray-500">{{ __('settings.template_' . $key . '_desc') }}</p>
                                        </div>
                                    </div>
                                    @if($currentInvoiceTemplate === $key)
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
                </div>

                <!-- Offer Templates Tab -->
                <div x-show="activeTab === 'offers'" x-transition>
                    <p class="text-sm text-gray-500 mb-4">{{ __('settings.offer_templates_desc') }}</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach($templates as $key => $template)
                            <label class="relative cursor-pointer group">
                                <input type="radio" name="offer_template" value="{{ $key }}" class="sr-only peer"
                                       {{ $currentOfferTemplate === $key ? 'checked' : '' }}>
                                <div class="border-2 rounded-xl p-4 transition-all
                                            peer-checked:border-green-500 peer-checked:bg-green-50
                                            hover:border-gray-300 border-gray-200">
                                    @include('settings._template_preview', ['key' => $key])
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ __('settings.template_' . $key) }}</p>
                                            <p class="text-xs text-gray-500">{{ __('settings.template_' . $key . '_desc') }}</p>
                                        </div>
                                    </div>
                                    @if($currentOfferTemplate === $key)
                                        <div class="absolute top-2 right-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                                {{ __('settings.current') }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Hidden field for proforma (uses same as invoice) -->
                <input type="hidden" name="proforma_template" value="{{ $currentInvoiceTemplate }}">

                <div class="mt-6 flex justify-end">
                    <button type="submit"
                            class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                        {{ __('settings.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-settings-layout>

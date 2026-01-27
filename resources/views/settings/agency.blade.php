<x-settings-layout>
    <div class="p-6">
        <div class="max-w-xl">
            <h3 class="text-lg font-semibold text-gray-900">{{ __('settings.agency_info') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ __('settings.agency_info_desc') }}</p>

            <form method="post" action="{{ route('settings.agency.update') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
                @csrf
                @method('put')

                <!-- Logo Upload -->
                <div x-data="{
                    preview: '{{ $agency?->logo_url }}',
                    hasLogo: {{ $agency?->logo ? 'true' : 'false' }},
                    removeLogo: false
                }">
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('settings.logo') }}</label>
                    <div class="flex items-center gap-4">
                        <div class="w-20 h-20 rounded-lg bg-gray-100 border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden">
                            <template x-if="preview && !removeLogo">
                                <img :src="preview" class="w-full h-full object-contain">
                            </template>
                            <template x-if="!preview || removeLogo">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </template>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label class="cursor-pointer inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ __('settings.upload_logo') }}
                                <input type="file" name="logo" class="hidden" accept="image/*"
                                       @change="const file = $event.target.files[0]; if(file) { preview = URL.createObjectURL(file); removeLogo = false; }">
                            </label>
                            <template x-if="hasLogo && !removeLogo">
                                <button type="button" @click="removeLogo = true"
                                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-red-600 bg-white border border-gray-300 rounded-lg hover:bg-red-50 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    {{ __('settings.remove_logo') }}
                                </button>
                            </template>
                            <input type="hidden" name="remove_logo" :value="removeLogo ? '1' : '0'">
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">{{ __('settings.logo_hint') }}</p>
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">{{ __('settings.agency_name') }}</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $agency?->name) }}"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="tax_number" class="block text-sm font-medium text-gray-700">{{ __('settings.tax_number') }}</label>
                        <input type="text" name="tax_number" id="tax_number" value="{{ old('tax_number', $agency?->tax_number) }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @error('tax_number')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="registration_number" class="block text-sm font-medium text-gray-700">{{ __('settings.registration_number') }}</label>
                        <input type="text" name="registration_number" id="registration_number" value="{{ old('registration_number', $agency?->registration_number) }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @error('registration_number')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700">{{ __('settings.address') }}</label>
                    <input type="text" name="address" id="address" value="{{ old('address', $agency?->address) }}"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    @error('address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700">{{ __('settings.city') }}</label>
                        <input type="text" name="city" id="city" value="{{ old('city', $agency?->city) }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>

                    <div>
                        <label for="postal_code" class="block text-sm font-medium text-gray-700">{{ __('settings.postal_code') }}</label>
                        <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $agency?->postal_code) }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>

                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700">{{ __('settings.country') }}</label>
                        <input type="text" name="country" id="country" value="{{ old('country', $agency?->country ?? 'Македонија') }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">{{ __('settings.phone') }}</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $agency?->phone) }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">{{ __('settings.email') }}</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $agency?->email) }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                </div>

                <div>
                    <label for="website" class="block text-sm font-medium text-gray-700">{{ __('settings.website') }}</label>
                    <input type="url" name="website" id="website" value="{{ old('website', $agency?->website) }}" placeholder="https://"
                           class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                </div>

                <div class="pt-4">
                    <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                        {{ __('settings.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-settings-layout>

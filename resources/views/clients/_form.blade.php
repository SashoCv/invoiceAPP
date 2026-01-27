<div class="space-y-6">
    <!-- Company / Name -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="company" class="block text-sm font-medium text-gray-700">{{ __('clients.company') }}</label>
            <input type="text" name="company" id="company" value="{{ old('company', $client->company ?? '') }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                   placeholder="{{ __('clients.company_placeholder') }}">
            @error('company')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">{{ __('clients.contact_person') }} *</label>
            <input type="text" name="name" id="name" value="{{ old('name', $client->name ?? '') }}" required
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Tax / Registration Numbers -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="tax_number" class="block text-sm font-medium text-gray-700">{{ __('clients.tax_number') }}</label>
            <input type="text" name="tax_number" id="tax_number" value="{{ old('tax_number', $client->tax_number ?? '') }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                   placeholder="{{ __('clients.tax_number_placeholder') }}">
            @error('tax_number')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="registration_number" class="block text-sm font-medium text-gray-700">{{ __('clients.registration_number') }}</label>
            <input type="text" name="registration_number" id="registration_number" value="{{ old('registration_number', $client->registration_number ?? '') }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            @error('registration_number')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Contact Info -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">{{ __('clients.email') }}</label>
            <input type="email" name="email" id="email" value="{{ old('email', $client->email ?? '') }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700">{{ __('clients.phone') }}</label>
            <input type="text" name="phone" id="phone" value="{{ old('phone', $client->phone ?? '') }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            @error('phone')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Address -->
    <div>
        <label for="address" class="block text-sm font-medium text-gray-700">{{ __('clients.address') }}</label>
        <input type="text" name="address" id="address" value="{{ old('address', $client->address ?? '') }}"
               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
        @error('address')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- City / Postal / Country -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label for="city" class="block text-sm font-medium text-gray-700">{{ __('clients.city') }}</label>
            <input type="text" name="city" id="city" value="{{ old('city', $client->city ?? '') }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
        </div>

        <div>
            <label for="postal_code" class="block text-sm font-medium text-gray-700">{{ __('clients.postal_code') }}</label>
            <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $client->postal_code ?? '') }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
        </div>

        <div>
            <label for="country" class="block text-sm font-medium text-gray-700">{{ __('clients.country') }}</label>
            <input type="text" name="country" id="country" value="{{ old('country', $client->country ?? 'Македонија') }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
        </div>
    </div>
</div>

<div class="space-y-6">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">{{ __('articles.name') }} *</label>
        <input type="text" name="name" id="name" value="{{ old('name', $article->name ?? '') }}" required
               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
               placeholder="{{ __('articles.name_placeholder') }}">
        @error('name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="description" class="block text-sm font-medium text-gray-700">{{ __('articles.description') }}</label>
        <textarea name="description" id="description" rows="2"
                  class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                  placeholder="{{ __('articles.description_placeholder') }}">{{ old('description', $article->description ?? '') }}</textarea>
        @error('description')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label for="unit" class="block text-sm font-medium text-gray-700">{{ __('articles.unit') }} *</label>
            <select name="unit" id="unit" required
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                @php
                    $units = ['час', 'парче', 'услуга', 'ден', 'месец', 'проект', 'страница', 'km', 'm²', 'kg'];
                    $currentUnit = old('unit', $article->unit ?? 'час');
                @endphp
                @foreach($units as $unit)
                    <option value="{{ $unit }}" {{ $currentUnit === $unit ? 'selected' : '' }}>{{ $unit }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="price" class="block text-sm font-medium text-gray-700">{{ __('articles.price') }} (ден.) *</label>
            <input type="number" name="price" id="price" step="0.01" min="0"
                   value="{{ old('price', $article->price ?? '') }}" required
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            @error('price')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="tax_rate" class="block text-sm font-medium text-gray-700">{{ __('articles.tax_rate') }} (%) *</label>
            <select name="tax_rate" id="tax_rate" required
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                @php
                    $rates = [0, 5, 10, 18];
                    $currentRate = old('tax_rate', $article->tax_rate ?? 18);
                @endphp
                @foreach($rates as $rate)
                    <option value="{{ $rate }}" {{ (float)$currentRate === (float)$rate ? 'selected' : '' }}>{{ $rate }}%</option>
                @endforeach
            </select>
        </div>
    </div>

    @if(isset($article) && $article->exists)
        <div class="flex items-center">
            <input type="checkbox" name="is_active" id="is_active" value="1"
                   {{ old('is_active', $article->is_active) ? 'checked' : '' }}
                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <label for="is_active" class="ml-2 text-sm text-gray-700">{{ __('articles.is_active') }}</label>
        </div>
    @endif
</div>

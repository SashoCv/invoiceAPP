<x-app-layout>
    <div x-data="offerForm()" x-init="init()">
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('offers.edit_offer') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $offer->formatted_number }}</p>
            </div>
        </div>

        <form action="{{ route('offers.update', $offer) }}" method="POST" @submit="validateForm">
            @csrf
            @method('PUT')

            <div class="max-w-5xl mx-auto space-y-6">
                <!-- Offer Header -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-4">
                        <!-- Offer Number -->
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('offers.offer_number') }} <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="text" name="offer_prefix" id="offer_prefix"
                                       value="{{ old('offer_prefix', $offer->offer_prefix) }}"
                                       placeholder="{{ __('offers.prefix_placeholder') }}"
                                       class="w-20 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <span class="text-gray-500 font-medium">{{ $offer->offer_year }}-</span>
                                <input type="number" name="offer_sequence" id="offer_sequence" required
                                       value="{{ old('offer_sequence', $offer->offer_sequence) }}"
                                       min="1"
                                       class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                        </div>

                        <!-- Client -->
                        <div>
                            <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('offers.client') }} <span class="text-red-500">*</span>
                            </label>
                            <select name="client_id" id="client_id" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="">{{ __('offers.select_client') }}</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id', $offer->client_id) == $client->id ? 'selected' : '' }}>
                                        {{ $client->display_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Currency -->
                        <div>
                            <label for="currency" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('offers.currency') }} <span class="text-red-500">*</span>
                            </label>
                            <select name="currency" id="currency" required x-model="currency" @change="updateCurrency()"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="MKD">MKD (ден.)</option>
                                <option value="EUR">EUR (€)</option>
                                <option value="USD">USD ($)</option>
                            </select>
                        </div>

                        <!-- Issue Date -->
                        <div>
                            <label for="issue_date" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('offers.issue_date') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="issue_date" id="issue_date" required
                                   value="{{ old('issue_date', $offer->issue_date->format('Y-m-d')) }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>

                        <!-- Valid Until -->
                        <div>
                            <label for="valid_until" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('offers.valid_until') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="valid_until" id="valid_until" required
                                   value="{{ old('valid_until', $offer->valid_until->format('Y-m-d')) }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>

                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('offers.status') }} <span class="text-red-500">*</span>
                            </label>
                            <select name="status" id="status" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="draft" {{ old('status', $offer->status) === 'draft' ? 'selected' : '' }}>{{ __('offers.status_draft') }}</option>
                                <option value="sent" {{ old('status', $offer->status) === 'sent' ? 'selected' : '' }}>{{ __('offers.status_sent') }}</option>
                                <option value="accepted" {{ old('status', $offer->status) === 'accepted' ? 'selected' : '' }}>{{ __('offers.status_accepted') }}</option>
                                <option value="rejected" {{ old('status', $offer->status) === 'rejected' ? 'selected' : '' }}>{{ __('offers.status_rejected') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Offer Title & Content -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="space-y-4">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('offers.offer_title') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="title" id="title" required
                                   value="{{ old('title', $offer->title) }}"
                                   placeholder="{{ __('offers.title_placeholder') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>

                        <div>
                            <label for="content" class="block text-sm font-medium text-gray-700 mb-1">{{ __('offers.content') }}</label>
                            <textarea name="content" id="content" rows="6"
                                      placeholder="{{ __('offers.content_placeholder') }}"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">{{ old('content', $offer->content) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Include Items Toggle -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="has_items" value="1" x-model="hasItems"
                               class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">{{ __('offers.include_items') }}</span>
                    </label>
                </div>

                <!-- Offer Items (conditional) -->
                <div x-show="hasItems" x-transition class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between p-4 border-b border-gray-200">
                        <h2 class="font-semibold text-gray-900">{{ __('offers.items') }}</h2>
                        <button type="button" @click="addItem()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            {{ __('offers.add_item') }}
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium">{{ __('offers.description') }}</th>
                                    <th class="px-2 py-3 text-center font-medium w-20">{{ __('offers.quantity') }}</th>
                                    <th class="px-2 py-3 text-center font-medium w-28">{{ __('offers.unit_price') }}</th>
                                    <th class="px-2 py-3 text-center font-medium w-24">{{ __('offers.tax') }}</th>
                                    <th class="px-4 py-3 text-right font-medium w-32">{{ __('offers.item_total') }}</th>
                                    <th class="w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(item, index) in items" :key="index">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2">
                                            <input type="text" x-model="item.description"
                                                   :name="'items[' + index + '][description]'"
                                                   placeholder="{{ __('offers.description') }}"
                                                   :required="hasItems"
                                                   class="w-full px-2 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="number" x-model="item.quantity"
                                                   :name="'items[' + index + '][quantity]'"
                                                   @input="calculateTotals()"
                                                   step="0.01" min="0.01" :required="hasItems"
                                                   class="w-full px-2 py-1.5 text-center border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="number" x-model="item.unit_price"
                                                   :name="'items[' + index + '][unit_price]'"
                                                   @input="calculateTotals()"
                                                   step="0.01" min="0" :required="hasItems"
                                                   class="w-full px-2 py-1.5 text-center border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                        </td>
                                        <td class="px-2 py-2">
                                            <select x-model="item.tax_rate"
                                                    :name="'items[' + index + '][tax_rate]'"
                                                    @change="calculateTotals()"
                                                    class="w-full py-1.5 text-center border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white pr-6 pl-2">
                                                <option value="0">0%</option>
                                                <option value="5">5%</option>
                                                <option value="10">10%</option>
                                                <option value="18">18%</option>
                                            </select>
                                        </td>
                                        <td class="px-4 py-2 text-right font-medium text-gray-900" x-text="formatNumber(getItemTotal(item)) + ' ' + currencySymbol"></td>
                                        <td class="px-2 py-2">
                                            <button type="button" @click="removeItem(index)" x-show="items.length > 1"
                                                    class="p-1 text-gray-400 hover:text-red-600 rounded hover:bg-red-50 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Totals -->
                    <div class="border-t border-gray-200 bg-gray-50 p-4">
                        <div class="flex justify-end">
                            <div class="w-64 space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">{{ __('offers.subtotal') }}</span>
                                    <span class="font-medium" x-text="formatNumber(subtotal) + ' ' + currencySymbol"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">{{ __('offers.tax') }}</span>
                                    <span class="font-medium" x-text="formatNumber(taxAmount) + ' ' + currencySymbol"></span>
                                </div>
                                <div class="flex justify-between pt-2 border-t border-gray-300 text-base">
                                    <span class="font-semibold">{{ __('offers.total') }}</span>
                                    <span class="font-bold" x-text="formatNumber(total) + ' ' + currencySymbol"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('offers.notes') }}</label>
                    <textarea name="notes" rows="2"
                              placeholder="{{ __('offers.notes_placeholder') }}"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">{{ old('notes', $offer->notes) }}</textarea>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('offers.show', $offer) }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">
                        {{ __('offers.cancel') }}
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ __('offers.save') }}
                    </button>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function offerForm() {
            return {
                items: [],
                subtotal: 0,
                taxAmount: 0,
                total: 0,
                currency: '{{ $offer->currency ?? "MKD" }}',
                currencySymbol: '{{ $offer->currency_symbol ?? "ден." }}',
                currencySymbols: { 'MKD': 'ден.', 'EUR': '€', 'USD': '$' },
                articles: @json($articles),
                hasItems: {{ $offer->has_items ? 'true' : 'false' }},

                init() {
                    @php
                        $existingItems = $offer->items->map(function($item) {
                            return [
                                'description' => $item->description,
                                'quantity' => (float) $item->quantity,
                                'unit_price' => (float) $item->unit_price,
                                'tax_rate' => (float) ($item->tax_rate ?? 0),
                            ];
                        });
                    @endphp
                    this.items = @json($existingItems);

                    if (this.items.length === 0) {
                        this.items = [{
                            description: '',
                            quantity: 1,
                            unit_price: 0,
                            tax_rate: 18
                        }];
                    }

                    this.calculateTotals();
                },

                updateCurrency() {
                    this.currencySymbol = this.currencySymbols[this.currency] || this.currency;
                },

                addItem() {
                    this.items.push({
                        description: '',
                        quantity: 1,
                        unit_price: 0,
                        tax_rate: 18
                    });
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                    this.calculateTotals();
                },

                getItemTotal(item) {
                    const subtotal = (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
                    const tax = subtotal * (parseFloat(item.tax_rate) || 0) / 100;
                    return subtotal + tax;
                },

                calculateTotals() {
                    this.subtotal = this.items.reduce((sum, item) => {
                        return sum + (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
                    }, 0);
                    this.taxAmount = this.items.reduce((sum, item) => {
                        const itemSubtotal = (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
                        return sum + (itemSubtotal * (parseFloat(item.tax_rate) || 0) / 100);
                    }, 0);
                    this.total = this.subtotal + this.taxAmount;
                },

                formatNumber(num) {
                    return new Intl.NumberFormat('mk-MK', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(num || 0);
                },

                validateForm(e) {
                    if (this.hasItems && this.items.length === 0) {
                        e.preventDefault();
                        return false;
                    }
                    return true;
                }
            }
        }
    </script>
    @endpush
</x-app-layout>

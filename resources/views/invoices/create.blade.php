<x-app-layout>
    <div x-data="invoiceForm()" x-init="init()">
        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('invoices.create_invoice') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('invoices.subtitle') }}</p>
            </div>
        </div>

        <form action="{{ route('invoices.store') }}" method="POST" @submit="validateForm">
            @csrf

            <div class="max-w-5xl mx-auto space-y-6">
                <!-- Invoice Header -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <!-- Invoice Number -->
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('invoices.invoice_number') }} <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="text" name="invoice_prefix" id="invoice_prefix"
                                       value="{{ old('invoice_prefix') }}"
                                       placeholder="{{ __('invoices.prefix_placeholder') }}"
                                       class="w-20 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <span class="text-gray-500 font-medium">{{ $currentYear }}-</span>
                                <input type="number" name="invoice_sequence" id="invoice_sequence" required
                                       value="{{ old('invoice_sequence', $nextSequence) }}"
                                       min="1"
                                       class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                            @error('invoice_prefix')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            @error('invoice_sequence')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Client -->
                        <div>
                            <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('invoices.client') }} <span class="text-red-500">*</span>
                            </label>
                            <select name="client_id" id="client_id" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="">{{ __('invoices.select_client') }}</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                        {{ $client->display_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('client_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Currency -->
                        <div>
                            <label for="currency" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('invoices.currency') }} <span class="text-red-500">*</span>
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
                                {{ __('invoices.issue_date') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="issue_date" id="issue_date" required
                                   value="{{ old('issue_date', date('Y-m-d')) }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            @error('issue_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Due Date -->
                        <div>
                            <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('invoices.due_date') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="due_date" id="due_date" required
                                   value="{{ old('due_date', date('Y-m-d', strtotime('+15 days'))) }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            @error('due_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Invoice Items -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between p-4 border-b border-gray-200">
                        <h2 class="font-semibold text-gray-900">{{ __('invoices.items') }}</h2>
                        <button type="button" @click="addItem()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            {{ __('invoices.add_item') }}
                        </button>
                    </div>

                    <!-- Items Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium">{{ __('invoices.description') }}</th>
                                    <th class="px-2 py-3 text-center font-medium w-20">{{ __('invoices.quantity') }}</th>
                                    <th class="px-2 py-3 text-center font-medium w-28">{{ __('invoices.unit_price') }}</th>
                                    <th class="px-2 py-3 text-center font-medium w-24">{{ __('invoices.tax') }}</th>
                                    <th class="px-4 py-3 text-right font-medium w-32">{{ __('invoices.item_total') }}</th>
                                    <th class="w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(item, index) in items" :key="index">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2">
                                            <select x-model="item.article_id" @change="selectArticle(index)"
                                                    class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500 mb-1.5 text-gray-500">
                                                <option value="">{{ __('invoices.select_article') }}</option>
                                                @foreach($articles as $article)
                                                    <option value="{{ $article->id }}">{{ $article->name }}</option>
                                                @endforeach
                                            </select>
                                            <input type="text" x-model="item.description"
                                                   :name="'items[' + index + '][description]'"
                                                   placeholder="{{ __('invoices.description') }}"
                                                   required
                                                   class="w-full px-2 py-1.5 border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="number" x-model="item.quantity"
                                                   :name="'items[' + index + '][quantity]'"
                                                   @input="calculateTotals()"
                                                   step="0.01" min="0.01" required
                                                   class="w-full px-2 py-1.5 text-center border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                        </td>
                                        <td class="px-2 py-2">
                                            <input type="number" x-model="item.unit_price"
                                                   :name="'items[' + index + '][unit_price]'"
                                                   @input="calculateTotals()"
                                                   step="0.01" min="0" required
                                                   class="w-full px-2 py-1.5 text-center border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                        </td>
                                        <td class="px-2 py-2">
                                            <select x-model="item.tax_rate"
                                                    :name="'items[' + index + '][tax_rate]'"
                                                    @change="calculateTotals()"
                                                    class="w-full py-1.5 text-center border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white pr-6 pl-2"
                                                    style="background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%236b7280%27 stroke-width=%272%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27%3e%3cpolyline points=%276 9 12 15 18 9%27%3e%3c/polyline%3e%3c/svg%3e'); background-repeat: no-repeat; background-position: right 4px center; background-size: 14px;">
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

                    @error('items')
                        <p class="px-4 py-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <!-- Totals -->
                    <div class="border-t border-gray-200 bg-gray-50 p-4">
                        <div class="flex justify-end">
                            <div class="w-64 space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">{{ __('invoices.subtotal') }}</span>
                                    <span class="font-medium" x-text="formatNumber(subtotal) + ' ' + currencySymbol"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">{{ __('invoices.tax') }}</span>
                                    <span class="font-medium" x-text="formatNumber(taxAmount) + ' ' + currencySymbol"></span>
                                </div>
                                <div class="flex justify-between pt-2 border-t border-gray-300 text-base">
                                    <span class="font-semibold">{{ __('invoices.total') }}</span>
                                    <span class="font-bold" x-text="formatNumber(total) + ' ' + currencySymbol"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('invoices.notes') }}</label>
                    <textarea name="notes" rows="2"
                              placeholder="{{ __('invoices.notes_placeholder') }}"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">{{ old('notes') }}</textarea>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('invoices.index') }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">
                        {{ __('invoices.cancel') }}
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ __('invoices.save') }}
                    </button>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function invoiceForm() {
            return {
                items: [],
                subtotal: 0,
                taxAmount: 0,
                total: 0,
                currency: 'MKD',
                currencySymbol: 'ден.',
                currencySymbols: { 'MKD': 'ден.', 'EUR': '€', 'USD': '$' },
                articles: @json($articles),

                init() {
                    this.items = [{
                        article_id: '',
                        description: '',
                        quantity: 1,
                        unit_price: 0,
                        tax_rate: 18
                    }];
                    this.calculateTotals();
                },

                updateCurrency() {
                    this.currencySymbol = this.currencySymbols[this.currency] || this.currency;
                },

                addItem() {
                    this.items.push({
                        article_id: '',
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

                selectArticle(index) {
                    const articleId = this.items[index].article_id;
                    if (articleId) {
                        const article = this.articles.find(a => a.id == articleId);
                        if (article) {
                            this.items[index].description = article.name + (article.description ? ' - ' + article.description : '');
                            this.items[index].unit_price = parseFloat(article.price);
                            this.items[index].tax_rate = parseFloat(article.tax_rate);
                            this.calculateTotals();
                        }
                    }
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
                    if (this.items.length === 0) {
                        e.preventDefault();
                        alert('{{ __("invoices.items") }}');
                        return false;
                    }
                    return true;
                }
            }
        }
    </script>
    @endpush
</x-app-layout>

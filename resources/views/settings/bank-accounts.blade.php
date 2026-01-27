<x-settings-layout>
    <div class="p-6" x-data="{ deleteModal: false, deleteForm: null, deleteName: '' }">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ __('settings.bank_accounts_title') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('settings.bank_accounts_desc') }}</p>
            </div>
            <button type="button" onclick="document.getElementById('add-account-modal').classList.remove('hidden')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('settings.add_account') }}
            </button>
        </div>

        @if($bankAccounts->isEmpty())
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                <p class="mt-4 text-gray-500">{{ __('settings.no_accounts') }}</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($bankAccounts as $account)
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg {{ $account->type === 'denar' ? 'bg-blue-100' : 'bg-emerald-100' }} flex items-center justify-center">
                                <span class="text-sm font-bold {{ $account->type === 'denar' ? 'text-blue-600' : 'text-emerald-600' }}">
                                    {{ $account->currency }}
                                </span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold text-gray-900">{{ $account->bank_name }}</p>
                                    @if($account->user_id)
                                        <span class="px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">{{ __('settings.personal') }}</span>
                                    @else
                                        <span class="px-2 py-0.5 text-xs font-medium bg-purple-100 text-purple-700 rounded-full">{{ __('settings.agency') }}</span>
                                    @endif
                                    @if($account->is_default)
                                        <span class="px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-700 rounded-full">{{ __('settings.is_default') }}</span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-600">{{ $account->account_number }}</p>
                                @if($account->iban)
                                    <p class="text-xs text-gray-500">IBAN: {{ $account->iban }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <form id="delete-form-{{ $account->id }}" method="POST" action="{{ route('settings.bank-accounts.destroy', $account) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="button"
                                        @click="deleteModal = true; deleteForm = 'delete-form-{{ $account->id }}'; deleteName = '{{ $account->bank_name }}'"
                                        class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-white transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Delete Confirmation Modal -->
        <div x-show="deleteModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" @click="deleteModal = false"></div>
                <div x-show="deleteModal"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="relative bg-white rounded-xl shadow-xl w-80 p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">{{ __('settings.delete_bank_account') }}</h3>
                    <p class="text-sm text-gray-500 text-center mb-6">
                        {{ __('settings.delete_bank_account_confirm') }} <span class="font-medium text-gray-900" x-text="deleteName"></span>?
                    </p>
                    <div class="flex gap-3">
                        <button type="button" @click="deleteModal = false"
                                class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            {{ __('settings.cancel') }}
                        </button>
                        <button type="button"
                                @click="document.getElementById(deleteForm).submit()"
                                class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                            {{ __('settings.delete') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Account Modal -->
    <div id="add-account-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('add-account-modal').classList.add('hidden')"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('settings.add_account') }}</h3>

                <form method="POST" action="{{ route('settings.bank-accounts.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="bank_name" class="block text-sm font-medium text-gray-700">{{ __('settings.bank_name') }}</label>
                        <input type="text" name="bank_name" id="bank_name" required
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>

                    <div>
                        <label for="account_number" class="block text-sm font-medium text-gray-700">{{ __('settings.account_number') }}</label>
                        <input type="text" name="account_number" id="account_number" required
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="iban" class="block text-sm font-medium text-gray-700">{{ __('settings.iban') }}</label>
                            <input type="text" name="iban" id="iban"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>

                        <div>
                            <label for="swift" class="block text-sm font-medium text-gray-700">{{ __('settings.swift') }}</label>
                            <input type="text" name="swift" id="swift"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700">{{ __('settings.account_type') }}</label>
                            <select name="type" id="type"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="denar">{{ __('settings.type_denar') }}</option>
                                <option value="foreign">{{ __('settings.type_foreign') }}</option>
                            </select>
                        </div>

                        <div>
                            <label for="currency" class="block text-sm font-medium text-gray-700">{{ __('settings.currency') }}</label>
                            <select name="currency" id="currency"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="MKD">MKD</option>
                                <option value="EUR">EUR</option>
                                <option value="USD">USD</option>
                                <option value="GBP">GBP</option>
                                <option value="CHF">CHF</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('settings.owner_type') }}</label>
                        <div class="flex gap-4">
                            <label class="flex items-center">
                                <input type="radio" name="owner_type" value="user" checked
                                       class="border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">{{ __('settings.personal') }}</span>
                            </label>
                            <label class="flex items-center {{ !$hasAgency ? 'opacity-50' : '' }}">
                                <input type="radio" name="owner_type" value="agency" {{ !$hasAgency ? 'disabled' : '' }}
                                       class="border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">{{ __('settings.agency') }}</span>
                            </label>
                        </div>
                        @if(!$hasAgency)
                            <p class="mt-1 text-xs text-gray-500">{{ __('settings.create_agency_first') }}</p>
                        @endif
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_default" id="is_default" value="1"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label for="is_default" class="ml-2 text-sm text-gray-700">{{ __('settings.is_default') }}</label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" onclick="document.getElementById('add-account-modal').classList.add('hidden')"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            {{ __('settings.cancel') }}
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800 transition-colors">
                            {{ __('settings.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-settings-layout>

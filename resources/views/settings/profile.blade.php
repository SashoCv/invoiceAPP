<x-settings-layout>
    <div class="divide-y divide-gray-200">
        <!-- Profile Information -->
        <div class="p-6">
            <div class="max-w-xl">
                <h3 class="text-lg font-semibold text-gray-900">{{ __('settings.profile_info') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('settings.profile_info_desc') }}</p>

                <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
                    @csrf
                    @method('patch')

                    <!-- Avatar -->
                    <div x-data="{
                        preview: null,
                        fileName: null,
                        handleFileSelect(event) {
                            const file = event.target.files[0];
                            if (file) {
                                this.fileName = file.name;
                                const reader = new FileReader();
                                reader.onload = (e) => {
                                    this.preview = e.target.result;
                                };
                                reader.readAsDataURL(file);
                            }
                        }
                    }">
                        <label class="block text-sm font-medium text-gray-700 mb-3">{{ __('settings.avatar') }}</label>
                        <div class="flex items-center gap-4">
                            <div class="relative">
                                <!-- Preview or Current Avatar -->
                                <template x-if="preview">
                                    <img :src="preview" alt="Preview" class="w-20 h-20 rounded-full object-cover ring-2 ring-blue-500">
                                </template>
                                <template x-if="!preview">
                                    @if($user->avatar)
                                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-20 h-20 rounded-full object-cover">
                                    @else
                                        <div class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center">
                                            <span class="text-2xl font-semibold text-gray-500">{{ $user->initials }}</span>
                                        </div>
                                    @endif
                                </template>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label for="avatar" class="cursor-pointer inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    {{ __('settings.change_avatar') }}
                                    <input type="file" name="avatar" id="avatar" class="hidden" accept="image/*" @change="handleFileSelect($event)">
                                </label>
                                <!-- Show selected file name -->
                                <template x-if="fileName">
                                    <span class="text-xs text-green-600 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span x-text="fileName" class="truncate max-w-[150px]"></span>
                                    </span>
                                </template>
                                @if($user->avatar)
                                    <button type="button" onclick="document.getElementById('remove_avatar').value = '1'; this.form.submit();"
                                            class="text-sm text-red-600 hover:text-red-700">
                                        {{ __('settings.remove_avatar') }}
                                    </button>
                                    <input type="hidden" name="remove_avatar" id="remove_avatar" value="0">
                                @endif
                            </div>
                        </div>
                        @error('avatar')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Name fields -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700">{{ __('settings.first_name') }}</label>
                            <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $user->first_name) }}"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            @error('first_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700">{{ __('settings.last_name') }}</label>
                            <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $user->last_name) }}"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            @error('last_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Email and Phone -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">{{ __('settings.email') }}</label>
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">{{ __('settings.phone') }}</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                            {{ __('settings.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Update Password -->
        <div class="p-6">
            <div class="max-w-xl">
                <h3 class="text-lg font-semibold text-gray-900">{{ __('settings.password') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('settings.password_desc') }}</p>

                <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-4">
                    @csrf
                    @method('put')

                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700">{{ __('settings.current_password') }}</label>
                        <input type="password" name="current_password" id="current_password"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @error('current_password', 'updatePassword')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">{{ __('settings.new_password') }}</label>
                        <input type="password" name="password" id="password"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @error('password', 'updatePassword')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">{{ __('settings.confirm_password') }}</label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                            {{ __('settings.update_password') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-settings-layout>

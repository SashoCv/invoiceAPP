{{-- Success Toast --}}
@if(session('success') || session('status'))
<div x-data="{ show: true }"
     x-show="show"
     x-init="setTimeout(() => show = false, 4000)"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-x-8"
     x-transition:enter-end="opacity-100 translate-x-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-x-0"
     x-transition:leave-end="opacity-0 translate-x-8"
     class="fixed top-6 right-6 z-50">
    <div class="flex items-center gap-3 px-4 py-3 bg-white rounded-lg shadow-lg border border-gray-200">
        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center shrink-0">
            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-900">
                @if(session('success'))
                    {{ session('success') }}
                @else
                    {{ __('settings.saved') }}
                @endif
            </p>
        </div>
        <button @click="show = false" class="ml-2 p-1 text-gray-400 hover:text-gray-600 rounded">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
</div>
@endif

{{-- Error Toast --}}
@if(session('error') || $errors->any())
<div x-data="{ show: true }"
     x-show="show"
     x-init="setTimeout(() => show = false, 5000)"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-x-8"
     x-transition:enter-end="opacity-100 translate-x-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-x-0"
     x-transition:leave-end="opacity-0 translate-x-8"
     class="fixed top-6 right-6 z-50">
    <div class="flex items-center gap-3 px-4 py-3 bg-white rounded-lg shadow-lg border border-red-200">
        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center shrink-0">
            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-900">
                @if(session('error'))
                    {{ session('error') }}
                @elseif($errors->any())
                    {{ $errors->first() }}
                @endif
            </p>
        </div>
        <button @click="show = false" class="ml-2 p-1 text-gray-400 hover:text-gray-600 rounded">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
</div>
@endif

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

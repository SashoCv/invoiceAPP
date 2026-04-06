<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        * {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        @page {
            size: A4;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
    </style>
</head>
<body class="bg-white">
    <div class="max-w-4xl mx-auto">
        <div class="p-8">
            {{-- Header --}}
            <div class="flex justify-between items-start mb-8 pb-4 border-b-2 border-orange-600">
                <div>
                    @if($agency)
                        <h2 class="text-xl font-bold text-orange-600 mb-2">{{ $agency->name }}</h2>
                        <div class="text-sm text-gray-500 space-y-0.5">
                            @if($agency->address)<div>{{ $agency->address }}</div>@endif
                            @if($agency->city)<div>{{ $agency->postal_code }} {{ $agency->city }}</div>@endif
                            @if($agency->phone)<div>Тел: {{ $agency->phone }}</div>@endif
                            @if($agency->email)<div>Email: {{ $agency->email }}</div>@endif
                            @if($agency->tax_number)<div>ЕДБ: {{ $agency->tax_number }}</div>@endif
                        </div>
                    @endif
                </div>
                <div class="text-right">
                    <h1 class="text-3xl font-bold text-orange-600">{{ $docTitle }}</h1>
                    <div class="text-gray-500 mt-1">Бр. {{ $docNumber }}</div>
                </div>
            </div>

            {{-- Info Section --}}
            <div class="grid grid-cols-2 gap-8 mb-8">
                @if($client)
                <div>
                    <h3 class="text-sm font-bold text-orange-600 mb-3 uppercase tracking-wide">Клиент</h3>
                    <div class="font-semibold text-gray-900">{{ $client->company ?? $client->name }}</div>
                    <div class="text-sm text-gray-500 mt-1 space-y-0.5">
                        @if($client->address)<div>{{ $client->address }}</div>@endif
                        @if($client->city)<div>{{ $client->postal_code }} {{ $client->city }}</div>@endif
                        @if($client->tax_number)<div>ЕДБ: {{ $client->tax_number }}</div>@endif
                    </div>
                </div>
                @endif
                <div>
                    <h3 class="text-sm font-bold text-orange-600 mb-3 uppercase tracking-wide">Детали</h3>
                    <table class="text-sm">
                        <tbody>
                            <tr><td class="text-gray-500 pr-4 py-0.5">Датум:</td><td class="font-medium">{{ $issueDate }}</td></tr>
                            <tr><td class="text-gray-500 pr-4 py-0.5">Број на ставки:</td><td class="font-medium">{{ count($movements) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Items Table --}}
            @if(count($movements) > 0)
            <div class="mb-6">
                <table class="w-full">
                    <thead>
                        <tr class="bg-orange-600 text-white">
                            <th class="text-left px-4 py-3 text-sm font-semibold">Име</th>
                            <th class="text-right px-4 py-3 text-sm font-semibold">Количина</th>
                            <th class="text-right px-4 py-3 text-sm font-semibold">Пред</th>
                            <th class="text-right px-4 py-3 text-sm font-semibold">После</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movements as $index => $movement)
                        <tr class="{{ $index % 2 === 1 ? 'bg-gray-50' : 'bg-white' }}">
                            <td class="px-4 py-3 text-sm border-b border-gray-200">{{ $movement->article->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-right border-b border-gray-200 font-medium">{{ number_format($movement->quantity, 2, ',', ' ') }} {{ $movement->article->unit ?? '' }}</td>
                            <td class="px-4 py-3 text-sm text-right border-b border-gray-200 text-gray-500">{{ number_format($movement->quantity_before, 2, ',', ' ') }}</td>
                            <td class="px-4 py-3 text-sm text-right border-b border-gray-200 text-gray-500">{{ number_format($movement->quantity_after, 2, ',', ' ') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            {{-- Notes --}}
            @if($notes)
            <div class="mt-6 pt-4 border-t border-gray-200">
                <h3 class="text-sm font-bold text-orange-600 mb-2 uppercase">Забелешки</h3>
                <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ $notes }}</p>
            </div>
            @endif

            {{-- Footer --}}
            @if($agency)
            <div class="mt-8 pt-4 border-t border-gray-200 text-center text-xs text-gray-400">
                {{ collect([$agency->name, $agency->website, $agency->email])->filter()->implode(' | ') }}
            </div>
            @endif
        </div>
    </div>
</body>
</html>

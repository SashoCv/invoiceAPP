<?php

namespace App\Http\Controllers;

use App\Services\CurrencyConverter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'from' => ['required', 'string', 'max:3'],
            'to' => ['required', 'string', 'max:3'],
            'date' => ['nullable', 'date'],
        ]);

        $converter = new CurrencyConverter();
        $result = $converter->convert(
            (float) $validated['amount'],
            $validated['from'],
            $validated['to'],
            $validated['date'] ?? now()->toDateString(),
        );

        $rate = null;
        if ($validated['from'] !== $validated['to']) {
            // Show the effective rate for display
            $rate = $converter->convert(1, $validated['from'], $validated['to'], $validated['date'] ?? now()->toDateString());
        }

        return response()->json([
            'result' => round($result, 2),
            'rate' => $rate ? round($rate, 6) : null,
        ]);
    }
}

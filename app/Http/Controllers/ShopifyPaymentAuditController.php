<?php

namespace App\Http\Controllers;

use App\Services\ShopifyApiClient;
use App\Services\ShopifyOrderProcessor;
use App\Services\ShopifyPaymentAuditService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Проверка на плаќања — compares payments in Shopify with the orders in the program for a
 * period: double charges, amounts that differ, orders the program missed, orders cancelled
 * or refunded in Shopify, failed payment attempts and abandoned checkouts.
 */
class ShopifyPaymentAuditController extends Controller
{
    public function index(Request $request, ShopifyPaymentAuditService $audit): Response
    {
        $from = $this->date($request->input('date_from'), now()->startOfMonth());
        $to = $this->date($request->input('date_to'), now());
        if ($to->lt($from)) {
            $to = $from->copy();
        }

        $connection = $request->user()->shopifyConnection;
        $result = null;
        if ($connection && $request->boolean('run')) {
            try {
                $result = $audit->audit($connection, $from->toDateString(), $to->toDateString());
            } catch (\Throwable $e) {
                report($e);
                $result = ['error' => 'api', 'message' => $e->getMessage()];
            }
        }

        return Inertia::render('Shopify/PaymentCheck', [
            'connected' => (bool) $connection,
            'result' => $result,
            'filters' => [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
            ],
        ]);
    }

    /**
     * Bring in a paid Shopify order the program missed (e.g. a webhook that never arrived).
     */
    public function import(Request $request, int $shopifyOrderId, ShopifyOrderProcessor $processor): RedirectResponse
    {
        $connection = $request->user()->shopifyConnection;
        abort_unless($connection, 404);

        try {
            $order = (new ShopifyApiClient($connection))->getOrder($shopifyOrderId);
            $processor->processOrder($request->user()->id, $order);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('shopify.audit_imported', ['number' => $order['name'] ?? $shopifyOrderId]));
    }

    private function date(?string $value, Carbon $default): Carbon
    {
        try {
            return $value ? Carbon::createFromFormat('Y-m-d', $value) : $default->copy();
        } catch (\Exception $e) {
            return $default->copy();
        }
    }
}

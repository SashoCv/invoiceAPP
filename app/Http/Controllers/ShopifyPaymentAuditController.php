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

    /**
     * Bring in all the given missing orders, oldest first (so stock moves in order).
     */
    public function importAll(Request $request, ShopifyOrderProcessor $processor): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer'],
        ]);

        $connection = $request->user()->shopifyConnection;
        abort_unless($connection, 404);
        $client = new ShopifyApiClient($connection);
        set_time_limit(300);

        // One request per 250 orders (Shopify's /orders.json?ids=…) instead of one per order,
        // so a large batch finishes well within the web server's timeout
        $ids = array_values(array_unique(array_map('intval', $validated['ids'])));
        $orders = [];
        try {
            foreach (array_chunk($ids, 250) as $chunk) {
                array_push($orders, ...$client->getOrders(['ids' => implode(',', $chunk), 'status' => 'any', 'limit' => 250]));
            }
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', $e->getMessage());
        }
        $found = array_map(fn ($o) => (int) $o['id'], $orders);
        $failed = array_values(array_diff($ids, $found));

        usort($orders, fn ($a, $b) => strcmp($a['created_at'] ?? '', $b['created_at'] ?? ''));
        $imported = 0;
        foreach ($orders as $order) {
            try {
                $result = $processor->processOrder($request->user()->id, $order);
                if ($result?->wasRecentlyCreated) {
                    $imported++;
                }
            } catch (\Throwable $e) {
                report($e);
                $failed[] = $order['name'] ?? $order['id'];
            }
        }

        $message = __('shopify.audit_imported_all', ['count' => $imported]);
        if ($failed) {
            return back()->with('error', $message . ' ' . __('shopify.audit_import_failed', ['list' => implode(', ', $failed)]));
        }

        return back()->with('success', $message);
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

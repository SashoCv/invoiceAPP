<?php

namespace App\Services;

use App\Models\ShopifyConnection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Проверка на плаќања — reads orders (with every payment transaction) and abandoned
 * checkouts from Shopify for a period and points out what needs checking against the bank:
 * double charges, charged amounts that differ from the order, orders the program missed,
 * orders paid in the program but cancelled/refunded in Shopify, and abandoned checkouts
 * (with an offsite gateway like NLB, a customer who paid but never returned to the shop
 * leaves only an abandoned checkout — money at the bank, no order).
 */
class ShopifyPaymentAuditService
{
    private const ORDERS_QUERY = <<<'GQL'
        query ($q: String!, $after: String) {
          orders(first: 100, after: $after, query: $q, sortKey: CREATED_AT) {
            pageInfo { hasNextPage endCursor }
            nodes {
              legacyResourceId
              name
              createdAt
              cancelledAt
              displayFinancialStatus
              totalPriceSet { shopMoney { amount } }
              totalRefundedSet { shopMoney { amount } }
              customer { displayName }
              transactions(first: 25) {
                kind
                status
                gateway
                test
                createdAt
                amountSet { shopMoney { amount } }
              }
            }
          }
        }
        GQL;

    private const CHECKOUTS_QUERY = <<<'GQL'
        query ($q: String!, $after: String) {
          abandonedCheckouts(first: 100, after: $after, query: $q) {
            pageInfo { hasNextPage endCursor }
            nodes {
              name
              createdAt
              completedAt
              totalPriceSet { shopMoney { amount } }
              customer { displayName email }
            }
          }
        }
        GQL;

    /** Paid states in Shopify that the program should have */
    private const PAID_STATES = ['PAID', 'PARTIALLY_REFUNDED', 'REFUNDED'];

    public function audit(ShopifyConnection $connection, string $from, string $to): array
    {
        // Shopify filters by real instants: the shop's calendar days in its own timezone
        $fromUtc = Carbon::parse($from, StockValuationService::BUSINESS_TZ)->startOfDay()->utc();
        $toUtc = Carbon::parse($to, StockValuationService::BUSINESS_TZ)->endOfDay()->utc();
        $search = sprintf("created_at:>='%s' created_at:<='%s'", $fromUtc->toIso8601ZuluString(), $toUtc->toIso8601ZuluString());
        $client = new ShopifyApiClient($connection);

        try {
            $orders = $this->fetchAll($client, self::ORDERS_QUERY, 'orders', $search);
            $checkouts = $this->fetchAll($client, self::CHECKOUTS_QUERY, 'abandonedCheckouts', $search);
        } catch (ShopifyGraphqlException $e) {
            return ['error' => $e->isAccessDenied() ? 'access_denied' : 'api', 'message' => $e->getMessage()];
        }

        $result = $this->analyse($connection->user_id, $orders, $checkouts);

        // Without read_all_orders Shopify only returns orders from the last 60 days — older
        // periods come back empty or cut off rather than with an error
        $earliest = $orders ? Carbon::parse($orders[0]['createdAt']) : null;
        $result['limited'] = $fromUtc->lt(now()->subDays(60))
            && (!$earliest || $earliest->gt($fromUtc->copy()->addDays(2)));

        return $result;
    }

    /**
     * Build the findings from Shopify payloads (separate from fetching, so it can be tested).
     */
    public function analyse(int $userId, array $orders, array $checkouts): array
    {
        $known = DB::table('shopify_orders')->where('user_id', $userId)
            ->whereIn('shopify_order_id', array_map(fn ($o) => (int) $o['legacyResourceId'], $orders))
            ->get(['id', 'shopify_order_id', 'financial_status'])->keyBy('shopify_order_id');

        $findings = ['double_charge' => [], 'amount_mismatch' => [], 'missing' => [], 'status_differs' => [], 'refunds' => [], 'failed_attempts' => []];
        $paidTotal = 0.0;

        foreach ($orders as $o) {
            $total = (float) ($o['totalPriceSet']['shopMoney']['amount'] ?? 0);
            $refunded = (float) ($o['totalRefundedSet']['shopMoney']['amount'] ?? 0);
            $status = $o['displayFinancialStatus'] ?? '';
            $tx = array_values(array_filter($o['transactions'] ?? [], fn ($t) => empty($t['test'])));

            $charges = array_filter($tx, fn ($t) => in_array($t['kind'], ['SALE', 'CAPTURE'], true) && $t['status'] === 'SUCCESS');
            $charged = array_sum(array_map(fn ($t) => (float) $t['amountSet']['shopMoney']['amount'], $charges));
            $failed = array_filter($tx, fn ($t) => in_array($t['status'], ['FAILURE', 'ERROR'], true));
            // Сторна: money given back (refund) or a payment cancelled before settlement (void)
            $reversals = array_filter($tx, fn ($t) => in_array($t['kind'], ['REFUND', 'VOID'], true) && $t['status'] === 'SUCCESS');

            $local = $known->get((int) $o['legacyResourceId']);
            $row = [
                'shopify_id' => (int) $o['legacyResourceId'],
                'local_id' => $local->id ?? null,
                'number' => $o['name'],
                'created_at' => Carbon::parse($o['createdAt'])->setTimezone(StockValuationService::BUSINESS_TZ)->format('d.m.Y H:i'),
                'customer' => $o['customer']['displayName'] ?? null,
                'status' => $status,
                'cancelled' => !empty($o['cancelledAt']),
                'local_status' => $local->financial_status ?? null,
                'total' => round($total, 2),
                'charged' => round($charged, 2),
                'refunded' => round($refunded, 2),
                'reversed' => round(array_sum(array_map(fn ($t) => (float) $t['amountSet']['shopMoney']['amount'], $reversals)), 2),
                'transactions' => array_map(fn ($t) => [
                    'kind' => $t['kind'],
                    'status' => $t['status'],
                    'gateway' => $t['gateway'],
                    'amount' => round((float) $t['amountSet']['shopMoney']['amount'], 2),
                    'at' => Carbon::parse($t['createdAt'])->setTimezone(StockValuationService::BUSINESS_TZ)->format('d.m.Y H:i'),
                ], $tx),
            ];

            if (in_array($status, self::PAID_STATES, true)) {
                $paidTotal += $total;
            }
            if (count($charges) > 1 || $charged > $total + 1) {
                $findings['double_charge'][] = $row;
            } elseif ($charges && abs($charged - $total) > 1) {
                $findings['amount_mismatch'][] = $row;
            }
            if (!$local && in_array($status, self::PAID_STATES, true)) {
                $findings['missing'][] = $row;
            }
            if ($local && ($row['cancelled'] || in_array($status, ['REFUNDED', 'PARTIALLY_REFUNDED', 'VOIDED'], true))
                && $local->financial_status === 'paid') {
                $findings['status_differs'][] = $row;
            }
            if ($reversals || $refunded > 0 || $row['cancelled']) {
                $findings['refunds'][] = $row;
            }
            if ($failed && $charges) {
                $findings['failed_attempts'][] = $row;
            }
        }

        $abandoned = [];
        foreach ($checkouts as $c) {
            if (!empty($c['completedAt'])) {
                continue;
            }
            $abandoned[] = [
                'number' => $c['name'] ?? '',
                'created_at' => Carbon::parse($c['createdAt'])->setTimezone(StockValuationService::BUSINESS_TZ)->format('d.m.Y H:i'),
                'customer' => $c['customer']['displayName'] ?? null,
                'email' => $c['customer']['email'] ?? null,
                'total' => round((float) ($c['totalPriceSet']['shopMoney']['amount'] ?? 0), 2),
            ];
        }
        $findings['abandoned'] = $abandoned;

        return [
            'error' => null,
            'summary' => [
                'orders' => count($orders),
                'paid_orders_total' => round($paidTotal, 2),
                'in_program' => $known->count(),
            ] + array_map('count', $findings),
            'findings' => $findings,
        ];
    }

    private function fetchAll(ShopifyApiClient $client, string $query, string $root, string $search): array
    {
        $nodes = [];
        $after = null;
        while (true) {
            try {
                $data = $client->graphql($query, ['q' => $search, 'after' => $after])[$root] ?? [];
            } catch (ShopifyGraphqlException $e) {
                // Customer details are protected data; without that access, run without them
                if (!str_contains(strtolower($e->getMessage()), 'customer') || !str_contains($query, 'customer {')) {
                    throw $e;
                }
                $query = preg_replace('/\s*customer \{[^}]*\}/', '', $query);
                continue; // same page again, without customer fields
            }

            array_push($nodes, ...($data['nodes'] ?? []));
            $after = ($data['pageInfo']['hasNextPage'] ?? false) ? $data['pageInfo']['endCursor'] : null;
            if (!$after || count($nodes) >= 5000) {
                return $nodes;
            }
        }
    }
}

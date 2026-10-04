<?php

namespace App\Services;

use RuntimeException;

class ShopifyGraphqlException extends RuntimeException
{
    public function __construct(public array $errors)
    {
        parent::__construct(collect($errors)->pluck('message')->filter()->implode('; ') ?: 'Shopify GraphQL error');
    }

    /** Missing access scope (e.g. read_all_orders for orders older than 60 days) */
    public function isAccessDenied(): bool
    {
        return collect($this->errors)->contains(fn ($e) => ($e['extensions']['code'] ?? null) === 'ACCESS_DENIED'
            || str_contains(strtolower($e['message'] ?? ''), 'access denied'));
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    /** Reasons for manual corrections (Магацин → Корекција на залиха) */
    public const REASONS_IN = ['opening', 'surplus', 'other'];
    public const REASONS_OUT = ['shortage', 'writeoff', 'other'];

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'article_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'cost_price',
        'tax_rate',
        'retail_price',
        'document_date',
        'reason',
        'reference_type',
        'reference_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'cost_price' => 'decimal:4',
        'tax_rate' => 'decimal:2',
        'retail_price' => 'decimal:4',
        'document_date' => 'date:Y-m-d',
        'created_at' => 'datetime',
    ];

    /**
     * Entered by hand in Магацин (Корекција на залиха, or the stock added with the article) —
     * not written by a document, a Shopify order/refund, an inventory count or disabling tracking.
     * Only these can be deleted from the article's history.
     */
    public function isManual(): bool
    {
        if ($this->reference_type !== null || !in_array($this->type, ['receipt', 'issue', 'adjustment'], true)) {
            return false;
        }
        if ($this->reason === 'tracking_disabled') {
            return false;
        }
        foreach (['Restored: invoice #', 'Restored from bundle:', 'Bundle: ', 'Shopify refund for order ', 'Попис '] as $prefix) {
            if ($this->notes && str_starts_with($this->notes, $prefix)) {
                return false;
            }
        }

        return true;
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'reference_id')
            ->where('reference_type', 'goods_receipt');
    }
}

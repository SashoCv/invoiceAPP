<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'sku',
        'description',
        'unit',
        'price',
        'tax_rate',
        'is_active',
        'track_inventory',
        'stock_quantity',
        'low_stock_threshold',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'track_inventory' => 'boolean',
        'stock_quantity' => 'decimal:2',
        'low_stock_threshold' => 'decimal:2',
    ];

    protected $appends = ['stock_status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function bundles(): BelongsToMany
    {
        return $this->belongsToMany(Bundle::class, 'bundle_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function getPriceWithTaxAttribute(): float
    {
        return $this->price * (1 + $this->tax_rate / 100);
    }

    public function getStockStatusAttribute(): string
    {
        if (!$this->track_inventory) {
            return 'not_tracked';
        }

        if ($this->stock_quantity <= 0) {
            return 'out_of_stock';
        }

        if ($this->stock_quantity <= $this->low_stock_threshold) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    public function deductStock(float $quantity, ?string $referenceType = null, ?int $referenceId = null, ?string $notes = null): StockMovement
    {
        $before = $this->stock_quantity;
        $this->stock_quantity -= $quantity;
        $this->save();

        $type = match ($referenceType) {
            'invoice' => 'invoice_deduction',
            'shopify_order' => 'shopify_deduction',
            default => 'issue',
        };

        return $this->stockMovements()->create([
            'user_id' => $this->user_id,
            'type' => $type,
            'quantity' => -$quantity,
            'quantity_before' => $before,
            'quantity_after' => $this->stock_quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
        ]);
    }

    public function addStock(float $quantity, ?string $notes = null, string $type = 'receipt'): StockMovement
    {
        $before = $this->stock_quantity;
        $this->stock_quantity += $quantity;
        $this->save();

        return $this->stockMovements()->create([
            'user_id' => $this->user_id,
            'type' => $type,
            'quantity' => $quantity,
            'quantity_before' => $before,
            'quantity_after' => $this->stock_quantity,
            'notes' => $notes,
        ]);
    }
}

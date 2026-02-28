<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bundle extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'price',
        'tax_rate',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bundleItems(): HasMany
    {
        return $this->hasMany(BundleItem::class);
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'bundle_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function getTotalComponentPriceAttribute(): float
    {
        return $this->articles->sum(function ($article) {
            return $article->price * $article->pivot->quantity;
        });
    }

    public function deductComponentStocks(float $bundleQuantity, ?string $referenceType = null, ?int $referenceId = null): void
    {
        foreach ($this->bundleItems()->with('article')->get() as $bundleItem) {
            if ($bundleItem->article && $bundleItem->article->track_inventory) {
                $totalDeduction = $bundleItem->quantity * $bundleQuantity;
                $bundleItem->article->deductStock(
                    $totalDeduction,
                    $referenceType,
                    $referenceId,
                    "Bundle: {$this->name} (x{$bundleQuantity})"
                );
            }
        }
    }

    public function restoreComponentStocks(float $bundleQuantity, ?string $referenceType = null, ?int $referenceId = null): void
    {
        foreach ($this->bundleItems()->with('article')->get() as $bundleItem) {
            if ($bundleItem->article && $bundleItem->article->track_inventory) {
                $totalRestore = $bundleItem->quantity * $bundleQuantity;
                $bundleItem->article->addStock(
                    $totalRestore,
                    "Restored from bundle: {$this->name} (x{$bundleQuantity})",
                    'adjustment'
                );
            }
        }
    }
}

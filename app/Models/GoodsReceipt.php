<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    protected $fillable = [
        'user_id',
        'receipt_number',
        'date',
        'notes',
        'total_cost',
    ];

    protected $casts = [
        'date' => 'date',
        'total_cost' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'reference_id')
            ->where('reference_type', 'goods_receipt');
    }

    public static function generateReceiptNumber(int $userId): string
    {
        $lastReceipt = static::where('user_id', $userId)->orderByDesc('id')->first();
        $nextSeq = $lastReceipt ? ((int) str_replace('PR-', '', $lastReceipt->receipt_number)) + 1 : 1;

        return 'PR-' . $nextSeq;
    }
}

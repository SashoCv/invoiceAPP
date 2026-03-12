<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsIssue extends Model
{
    protected $fillable = [
        'user_id',
        'client_id',
        'issue_number',
        'date',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'reference_id')
            ->where('reference_type', 'goods_issue');
    }

    public static function generateIssueNumber(int $userId): string
    {
        $lastIssue = static::where('user_id', $userId)->orderByDesc('id')->first();
        $nextSeq = $lastIssue ? ((int) str_replace('IS-', '', $lastIssue->issue_number)) + 1 : 1;

        return 'IS-' . $nextSeq;
    }
}

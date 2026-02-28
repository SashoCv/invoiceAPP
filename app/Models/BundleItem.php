<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BundleItem extends Model
{
    protected $fillable = [
        'bundle_id',
        'article_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}

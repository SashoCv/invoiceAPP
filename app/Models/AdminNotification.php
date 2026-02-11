<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminNotification extends Model
{
    protected $fillable = [
        'sent_by',
        'subject',
        'body',
        'audience',
        'sent_count',
        'status',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'sent_count' => 'integer',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function audienceLabel(): string
    {
        return match ($this->audience) {
            'all' => __('admin.audience_all'),
            'active' => __('admin.audience_active'),
            'expired' => __('admin.audience_expired'),
            default => $this->audience,
        };
    }
}

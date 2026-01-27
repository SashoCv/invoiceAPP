<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'name',
        'company',
        'email',
        'phone',
        'address',
        'city',
        'postal_code',
        'country',
        'tax_number',
        'registration_number',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->company ?: $this->name;
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->postal_code ? $this->postal_code . ' ' . $this->city : $this->city,
            $this->country,
        ]);
        return implode(', ', $parts);
    }
}

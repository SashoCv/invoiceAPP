<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agency extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'address',
        'city',
        'postal_code',
        'country',
        'phone',
        'email',
        'website',
        'tax_number',
        'registration_number',
        'logo',
        'display_currency',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo) {
            return asset('storage/' . $this->logo);
        }
        return null;
    }
}

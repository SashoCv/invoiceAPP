<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientBranch extends Model
{
    protected $fillable = [
        'client_id',
        'name',
        'address',
        'city',
        'postal_code',
        'country',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}

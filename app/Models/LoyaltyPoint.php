<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyPoint extends Model
{
    protected $fillable = [
        'customer_id', 'type', 'points',
        'reference_type', 'reference_id', 'note', 'expired_at',
    ];

    protected $casts = [
        'points'     => 'decimal:2',
        'expired_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_group_id', 'name', 'phone', 'email',
        'address', 'credit_limit', 'total_due',
        'loyalty_points', 'is_vip', 'is_blacklisted',
    ];

    protected $casts = [
        'credit_limit'   => 'decimal:2',
        'total_due'      => 'decimal:2',
        'loyalty_points' => 'decimal:2',
        'is_vip'         => 'boolean',
        'is_blacklisted' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function loyaltyPoints(): HasMany
    {
        return $this->hasMany(LoyaltyPoint::class);
    }
}
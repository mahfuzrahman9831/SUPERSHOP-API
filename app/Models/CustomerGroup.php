<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerGroup extends Model
{
    protected $fillable = [
        'name', 'discount_percentage', 'loyalty_multiplier',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'loyalty_multiplier'  => 'decimal:2',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
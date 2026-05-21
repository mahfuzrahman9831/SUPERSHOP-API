<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockMovementType extends Model
{
    protected $fillable = [
        'name', 'direction'
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'movement_type_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBundle extends Model
{
    protected $fillable = [
        'name', 'description', 'bundle_price', 'discount', 'is_active',
    ];

    protected $casts = [
        'bundle_price' => 'decimal:2',
        'discount'     => 'decimal:2',
        'is_active'    => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ProductBundleItem::class, 'bundle_id');
    }
}
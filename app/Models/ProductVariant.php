<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'name', 'value', 'barcode', 'sku',
        'last_purchase_price', 'default_selling_price',
        'stock_quantity', 'is_active',
    ];

    protected $casts = [
        'last_purchase_price'   => 'decimal:2',
        'default_selling_price' => 'decimal:2',
        'stock_quantity'        => 'decimal:2',
        'is_active'             => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
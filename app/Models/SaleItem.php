<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id', 'product_id', 'variant_id', 'tax_rate_id',
        'quantity', 'selling_price', 'cost_price',
        'discount', 'tax', 'profit', 'total',
    ];

    protected $casts = [
        'quantity'      => 'decimal:2',
        'selling_price' => 'decimal:2',
        'cost_price'    => 'decimal:2',
        'discount'      => 'decimal:2',
        'tax'           => 'decimal:2',
        'profit'        => 'decimal:2',
        'total'         => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function layers(): HasMany
    {
        return $this->hasMany(SaleItemLayer::class);
    }
}
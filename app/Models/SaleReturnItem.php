<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturnItem extends Model
{
    protected $fillable = [
        'sale_return_id', 'sale_item_id', 'product_id',
        'quantity', 'selling_price', 'cost_price', 'total',
    ];

    protected $casts = [
        'quantity'      => 'decimal:2',
        'selling_price' => 'decimal:2',
        'cost_price'    => 'decimal:2',
        'total'         => 'decimal:2',
    ];

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class, 'sale_return_id');
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function layers(): HasMany
    {
        return $this->hasMany(SaleReturnItemLayer::class, 'sale_return_item_id');
    }
}
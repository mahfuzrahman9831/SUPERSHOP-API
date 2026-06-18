<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnItemLayer extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sale_return_item_id', 'stock_layer_id',
        'quantity', 'cost_price', 'total_cost',
    ];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'cost_price' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function saleReturnItem(): BelongsTo
    {
        return $this->belongsTo(SaleReturnItem::class, 'sale_return_item_id');
    }

    public function stockLayer(): BelongsTo
    {
        return $this->belongsTo(ProductStockLayer::class, 'stock_layer_id');
    }
}
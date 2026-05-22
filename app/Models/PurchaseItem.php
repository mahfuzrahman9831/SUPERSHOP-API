<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id', 'product_id', 'batch_no', 'expiry_date',
        'quantity', 'received_quantity', 'purchase_price', 'total',
        'stock_layer_id',
    ];

    protected $casts = [
        'quantity'          => 'decimal:2',
        'received_quantity' => 'decimal:2',
        'purchase_price'    => 'decimal:2',
        'total'             => 'decimal:2',
        'expiry_date'       => 'date',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockLayer(): BelongsTo
    {
        return $this->belongsTo(ProductStockLayer::class, 'stock_layer_id');
    }
}
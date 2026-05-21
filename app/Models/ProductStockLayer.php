<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductStockLayer extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_id', 'warehouse_id', 'purchase_item_id',
        'purchase_price', 'quantity_in', 'quantity_remaining',
        'created_at',
    ];

    protected $casts = [
        'purchase_price'     => 'decimal:2',
        'quantity_in'        => 'decimal:2',
        'quantity_remaining' => 'decimal:2',
        'created_at'         => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
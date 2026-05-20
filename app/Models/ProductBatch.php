<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBatch extends Model
{
    protected $fillable = [
        'product_id', 'batch_no', 'expiry_date',
        'quantity', 'purchase_price',
    ];

    protected $casts = [
        'expiry_date'    => 'date',
        'quantity'       => 'decimal:2',
        'purchase_price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
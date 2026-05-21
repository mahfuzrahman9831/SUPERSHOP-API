<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_id', 'warehouse_id', 'movement_type_id',
        'quantity', 'reference_type', 'reference_id',
        'note', 'created_by', 'created_at',
    ];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function movementType(): BelongsTo
    {
        return $this->belongsTo(StockMovementType::class, 'movement_type_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
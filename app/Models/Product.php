<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'brand_id', 'category_id', 'unit_id', 'tax_rate_id',
        'name', 'slug', 'barcode', 'sku', 'description',
        'last_purchase_price', 'default_selling_price', 'min_selling_price',
        'stock_quantity', 'low_stock_alert', 'costing_method',
        'has_variants', 'has_serial', 'has_batch', 'is_active',
    ];

    protected $casts = [
        'last_purchase_price'   => 'decimal:2',
        'default_selling_price' => 'decimal:2',
        'min_selling_price'     => 'decimal:2',
        'stock_quantity'        => 'decimal:2',
        'low_stock_alert'       => 'decimal:2',
        'has_variants'          => 'boolean',
        'has_serial'            => 'boolean',
        'has_batch'             => 'boolean',
        'is_active'             => 'boolean',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function primaryImage(): ?ProductImage
    {
        return $this->images()->where('is_primary', true)->first();
    }
}
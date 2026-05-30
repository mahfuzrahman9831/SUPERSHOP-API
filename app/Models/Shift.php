<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'warehouse_id', 'opening_cash',
        'closing_cash', 'expected_cash', 'difference',
        'total_sales', 'total_expense', 'total_profit',
        'status', 'note', 'opened_at', 'closed_at',
    ];

    protected $casts = [
        'opening_cash'  => 'decimal:2',
        'closing_cash'  => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'difference'    => 'decimal:2',
        'total_sales'   => 'decimal:2',
        'total_expense' => 'decimal:2',
        'total_profit'  => 'decimal:2',
        'opened_at'     => 'datetime',
        'closed_at'     => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ShiftTransaction::class);
    }
}
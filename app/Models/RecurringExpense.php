<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringExpense extends Model
{
    protected $fillable = [
        'expense_category_id', 'name', 'amount',
        'frequency', 'next_due_date', 'is_active',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'next_due_date' => 'date',
        'is_active'     => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }
}
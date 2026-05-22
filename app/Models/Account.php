<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'name', 'type', 'account_number',
        'balance', 'is_default', 'is_active',
    ];

    protected $casts = [
        'balance'    => 'decimal:2',
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class);
    }
}
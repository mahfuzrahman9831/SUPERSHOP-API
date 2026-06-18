<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceCounter extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'module', 'prefix', 'current_number',
    ];
}
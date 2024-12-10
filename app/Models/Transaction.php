<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'amount',
        'currency',
        'status',
        'wallet',
        'transactionId',
    ];
}

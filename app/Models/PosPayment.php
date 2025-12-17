<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosPayment extends Model
{
    protected $fillable = [
        'order_id',
        'order_no',
        'paid_amount',
        'total_amount',
        'discount',
        'account_id',
        'notes',
        'added_by',
        'user_id',
    ];
}

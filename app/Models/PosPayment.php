<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}

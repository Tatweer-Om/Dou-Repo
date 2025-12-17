<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosPaymentExpence extends Model
{
    protected $fillable = [
        'order_id',
        'order_no',
        'total_amount',
        'accoun_id',
        'account_tax',
        'account_tax_fee',
        'added_by',
        'updated_by',
        'user_id',
    ];

    public function order()
    {
        return $this->belongsTo(PosOrders::class, 'order_id');
    }
}

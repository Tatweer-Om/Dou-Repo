<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosOrdersDetail extends Model
{
    protected $fillable = [
        'order_id',
        'order_no',
        'item_id',
        'item_barcode',
        'item_quantity',
        'restore_status',
        'item_discount_price',
        'item_price',
        'item_total',
        'item_tax',
        'item_profit',
        'added_by',
        'user_id',
        'branch_id',
    ];

    public function order()
    {
        return $this->belongsTo(PosOrders::class, 'order_id');
    }
}

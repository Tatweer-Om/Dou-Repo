<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosOrders extends Model
{
    protected $fillable = [
        'customer_id',
        'order_type',
        'delivery_area_id',
        'delivery_city_id',
        'delivery_address',
        'delivery_fee',
        'delivery_fee_paid',
        'item_count',
        'paid_amount',
        'total_amount',
        'discount_type',
        'total_discount',
        'profit',
        'return_status',
        'restore_status',
        'order_no',
        'notes',
        'added_by',
        'user_id',
    ];

    public function details()
    {
        return $this->hasMany(PosOrdersDetail::class, 'order_id');
    }

    public function payments()
    {
        return $this->hasMany(PosPayment::class, 'order_id');
    }
}

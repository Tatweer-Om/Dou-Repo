<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosOrders extends Model
{
    protected $fillable = [
        'customer_id',
        'order_type',
        'delivery_status',
        'item_count',
        'paid_amount',
        'total_amount',
        'discount_type',
        'total_discount',
        'delivery_charges',
        'delivery_paid',
        'profit',
        'return_status',
        'restore_status',
        'order_no',
        'notes',
        'added_by',
        'user_id',
        'channel_id',
    ];

    public function details()
    {
        return $this->hasMany(PosOrdersDetail::class, 'order_id');
    }

    public function payments()
    {
        return $this->hasMany(PosPayment::class, 'order_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }
}

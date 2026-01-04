<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosOrdersDetail extends Model
{
    protected $fillable = [
        'order_id',
        'order_no',
        'item_id',
        'item_barcode',
        'item_quantity',
        'color_id',
        'size_id',
        'restore_status',
        'item_discount_price',
        'item_price',
        'item_total',
        'item_tax',
        'item_profit',
        'added_by',
        'user_id',
        'branch_id',
        'channel_id',
    ];

    public function order()
    {
        return $this->belongsTo(PosOrders::class, 'order_id');
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class, 'item_id');
    }

    public function color()
    {
        return $this->belongsTo(Color::class, 'color_id');
    }

    public function size()
    {
        return $this->belongsTo(Size::class, 'size_id');
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }
}

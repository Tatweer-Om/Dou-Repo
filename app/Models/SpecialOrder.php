<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpecialOrder extends Model
{
    protected $fillable = [
        'special_order_no',
        'pos_order_id',
        'source',
        'customer_id',
        'customer_city',
        'customer_area',
        'customer_address',
        'send_as_gift',
        'gift_text',
        'notes',
        'shipping_fee',
        'discount',
        'status',
        'total_amount',
        'paid_amount',
        'account_id',
        'user_id',
        'added_by',
        'updated_by',
    ];

    protected $casts = [
        'send_as_gift' => 'boolean',
        'shipping_fee' => \App\Casts\SafeDecimalCast::class . ':3',
        'discount' => \App\Casts\SafeDecimalCast::class . ':3',
        'total_amount' => \App\Casts\SafeDecimalCast::class . ':3',
        'paid_amount' => \App\Casts\SafeDecimalCast::class . ':3',
    ];

    /**
     * Get the customer that owns the special order
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get all items for this special order
     */
    public function items(): HasMany
    {
        return $this->hasMany(SpecialOrderItem::class);
    }
}

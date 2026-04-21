<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaloonBookingPayment extends Model
{
    protected $fillable = [
        'saloon_booking_id',
        'account_id',
        'payment_method',
        'amount',
        'payment_at',
        'reference_no',
        'notes',
        'added_by',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'payment_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(SaloonBooking::class, 'saloon_booking_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}

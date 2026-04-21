<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaloonBookingHistory extends Model
{
    protected $fillable = [
        'saloon_booking_id',
        'action_type',
        'snapshot',
        'action_at',
        'action_by',
        'user_id',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'action_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(SaloonBooking::class, 'saloon_booking_id');
    }
}

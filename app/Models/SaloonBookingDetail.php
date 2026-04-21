<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaloonBookingDetail extends Model
{
    protected $fillable = [
        'saloon_booking_id',
        'services_json',
        'services_count',
        'services_total_amount',
    ];

    protected $casts = [
        'services_json' => 'array',
        'services_total_amount' => 'decimal:3',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(SaloonBooking::class, 'saloon_booking_id');
    }
}

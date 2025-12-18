<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'city_id',
        'area_id',
        'notes',
    ];

    /**
     * Get all special orders for this customer
     */
    public function specialOrders(): HasMany
    {
        return $this->hasMany(SpecialOrder::class);
    }

    /**
     * Get the city that belongs to this customer
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the area that belongs to this customer
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'governorate',
        'area',
        'area_id',
        'city_id',
        'address',
    ];

    /**
     * Get all special orders for this customer
     */
    public function specialOrders(): HasMany
    {
        return $this->hasMany(SpecialOrder::class);
    }
}

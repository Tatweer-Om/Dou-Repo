<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = [
        'area_id',
        'city_name_en',
        'city_name_ar',
        'delivery_charges',
        'notes',
        'added_by',
        'user_id',
        'updated_by',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}


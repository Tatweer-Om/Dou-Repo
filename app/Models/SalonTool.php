<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalonTool extends Model
{
    protected $fillable = [
        'name',
        'price',
        'notes',
        'added_by',
        'user_id',
        'updated_by',
    ];

    protected $casts = [
        'price' => 'decimal:3',
    ];
}

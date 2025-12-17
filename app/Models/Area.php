<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $fillable = [
        'area_name_en',
        'area_name_ar',
        'notes',
        'added_by',
        'user_id',
        'updated_by',
    ];
}


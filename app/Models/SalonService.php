<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalonService extends Model
{
    protected $fillable = [
        'name',
        'price', 
        'notes', 
         
        'added_by',
        'user_id',
        'updated_by',
    ];
}

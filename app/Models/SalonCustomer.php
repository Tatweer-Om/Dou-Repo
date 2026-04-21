<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalonCustomer extends Model
{
    protected $fillable = [
        'name',
        'phone', 
        'notes', 
        'added_by',
        'user_id',
        'updated_by',
    ];
}

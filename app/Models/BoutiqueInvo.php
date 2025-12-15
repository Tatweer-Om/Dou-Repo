<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoutiqueInvo extends Model
{
    protected $table = 'boutique_invos';
    
    protected $fillable = [
        'boutique_id',
        'boutique_name',
        'month',
        'payment_date',
        'status',
        'total_amount',
        'added_by',
        'user_id'
    ];
}

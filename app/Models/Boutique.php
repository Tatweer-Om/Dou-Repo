<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Boutique extends Model
{
    protected $fillable = [
        'boutique_name',
        'shelf_no',
        'monthly_rent',
        'rent_date',
        'status',
        'rent_invoice_status',
        'boutique_address',
        'added_by_by',
        'updated_by',
        'user_id'
    ];
}

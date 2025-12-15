<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settlement extends Model
{
    protected $fillable = [
        'settlement_code',
        'boutique_id',
        'boutique_name',
        'month',
        'date_from',
        'date_to',
        'number_of_items',
        'total_sales',
        'total_difference',
        'attachment_path',
        'attachment_name',
        'notes',
        'items_data',
        'added_by',
        'user_id',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'total_sales' => 'decimal:2',
        'items_data' => 'array',
    ];
}

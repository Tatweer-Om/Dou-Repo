<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = [
        'account_name',
        'account_branch',
        'account_no',
        'opening_balance',
        'commission',
        'account_type',
        'notes',
        'account_status',
        'added_by',
        'user_id',
        'updated_by',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:3',
        'commission' => 'decimal:2',
        'account_type' => 'integer',
        'account_status' => 'integer',
    ];
}


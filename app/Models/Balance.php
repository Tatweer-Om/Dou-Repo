<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Balance extends Model
{
    protected $fillable = [
        'account_name',
        'account_id',
        'account_no',
        'previous_balance',
        'new_total_amount',
        'source',
        'expense_amount',
        'expense_name',
        'expense_date',
        'expense_added_by',
        'expense_image',
        'notes',
        'added_by',
        'user_id',
    ];

    protected $casts = [
        'previous_balance' => 'decimal:3',
        'new_total_amount' => 'decimal:3',
        'expense_amount' => 'decimal:3',
        'expense_date' => 'date',
    ];

    // Relationships
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}


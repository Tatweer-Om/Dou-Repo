<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'category_id',
        'paid_by',
        'supplier_id',
        'reciept_no',
        'expense_name',
        'branch_id',
        'payment_method',
        'amount',
        'expense_date',
        'notes',
        'expense_image',
        'added_by',
        'user_id',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'expense_date' => 'date',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'payment_method');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}


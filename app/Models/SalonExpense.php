<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalonExpense extends Model
{
    protected $table = 'salon_expenses';

    protected $fillable = [
        'salon_expense_category_id',
        'supplier_id',
        'reciept_no',
        'expense_name',
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
        'amount'       => 'decimal:3',
        'expense_date' => 'date',
    ];

    public function category()
    {
        return $this->belongsTo(SalonExpenseCategory::class, 'salon_expense_category_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'payment_method');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

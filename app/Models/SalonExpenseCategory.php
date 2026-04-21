<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalonExpenseCategory extends Model
{
    protected $table = 'salon_expense_categories';

    protected $fillable = [
        'category_name',
        'notes',
        'added_by',
        'user_id',
        'updated_by',
    ];
}

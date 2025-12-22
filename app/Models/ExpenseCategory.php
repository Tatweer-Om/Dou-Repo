<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    protected $fillable = [
        'category_name',
        'notes',
        'added_by',
        'user_id',
        'updated_by',
    ];
}


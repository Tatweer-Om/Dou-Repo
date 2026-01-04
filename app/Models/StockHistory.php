<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockHistory extends Model
{
    protected $table = 'stock_histories'; // your table name

    // Fillable fields - allow mass assignment
    protected $fillable = [
        'stock_id',
        'size_id',
        'color_id',
        'old_qty',
        'changed_qty',
        'new_qty',
        'action_type',
        'pull_notes',
        'user_id',
        'added_by',
    ];
    
    // Relationships
    public function size()
    {
        return $this->belongsTo(Size::class, 'size_id');
    }
    
    public function color()
    {
        return $this->belongsTo(Color::class, 'color_id');
    }

}

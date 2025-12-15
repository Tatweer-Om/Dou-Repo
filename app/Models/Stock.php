<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = [
        'abaya_code',
        'design_name',
        'barcode',
        'abaya_notes',
        'category_id',
        'cost_price',
        'sales_price',
        'tailor_charges',
        'tailor_id',
        'quantity',
        'status',
        'notification_limit',
        'mode',
        'added_by',
        'updated_by',
        'user_id',
    ];

    public function colors()
    {
        return $this->hasMany(StockColor::class);
    }

    // Stock has many sizes
    public function sizes()
    {
        return $this->hasMany(StockSize::class);
    }

    // Stock has many images
    public function images()
    {
        return $this->hasMany(StockImage::class);
    }
    
    public function colorSizes()
    {
        return $this->hasMany(ColorSize::class);
    }

    // Stock belongs to a category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    protected $fillable = [
        'material_name',
        'description',
        'unit',
        'category',
        'buy_price',
        'sell_price',
        'rolls_count',
        'meters_per_roll',
        'material_image',
        'added_by',
        'user_id',
        'updated_by',
    ];

    /**
     * Get all tailor material assignments for this material
     */
    public function tailorMaterials(): HasMany
    {
        return $this->hasMany(TailorMaterial::class);
    }
}

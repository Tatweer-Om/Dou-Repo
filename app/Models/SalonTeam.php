<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalonTeam extends Model
{
    protected $fillable = [
        'code',
        'name',
        'name_ar',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function staff(): HasMany
    {
        return $this->hasMany(SalonStaff::class, 'team_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(SaloonBooking::class, 'team_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function displayName(?string $locale = null): string
    {
        $loc = $locale ?? app()->getLocale();
        if ($loc === 'ar' && filled($this->name_ar)) {
            return $this->name_ar;
        }

        return $this->name;
    }
}

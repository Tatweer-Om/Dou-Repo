<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalonStaff extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'team_id',
        'address',
        'staff_image',
        'added_by',
        'user_id',
        'updated_by',
    ];

    public function salonTeam(): BelongsTo
    {
        return $this->belongsTo(SalonTeam::class, 'team_id');
    }

    /**
     * App login user linked to this staff member (non-admin salon users).
     */
    public function linkedAppUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SMS extends Model
{
    protected $table = 's_m_s';
    
    // Disable timestamps if not needed, or keep them
    public $timestamps = true;

    protected $fillable = [
        'sms',
        'sms_status',
        'message_type',
        'added_by',
        'updated_by',
        'user_id',
    ];

    /**
     * Get decoded SMS text
     */
    public function getDecodedSmsAttribute()
    {
        return base64_decode($this->sms);
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transfer extends Model
{
    protected $fillable = [
        'transfer_code',
        'transfer_type',
        'channel_type',
        'date',
        'quantity',
        'from',
        'to',
        'stock_id',
        'boutique_id',
        'channel_id',
        'notes',
        'added_by',
        'user_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(TransferItem::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TransferItemHistory::class, 'transfer_id');
    }
}

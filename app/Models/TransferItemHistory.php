<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferItemHistory extends Model
{
    protected $table = 'transfer_item_histories';

    protected $fillable = [
        'transfer_id',
        'item_code',
        'item_size',
        'item_color',
        'item_previous_quantity',
        'quantity_action',
        'item_new_quantity',
        'quantity_pulled',
        'quantity_pushed',
        'added_by',
        'user_id',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class, 'transfer_id');
    }
}

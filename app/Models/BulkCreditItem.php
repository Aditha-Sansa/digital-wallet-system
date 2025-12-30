<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkCreditItem extends Model
{
    protected $fillable = [
        'activity_id',
        'user_id',
        'amount',
        'row_hash',
        'status',
        'error_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletLedgerTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'source',
        'reference_id',
        'idempotency_key',
    ];
}

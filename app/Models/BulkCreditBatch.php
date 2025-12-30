<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkCreditBatch extends Model
{
    protected $fillable = [
        'activity_id',
        'total_chunks',
        'processed_chunks',
        'status',
    ];
}

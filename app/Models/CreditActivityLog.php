<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditActivityLog extends Model
{
    protected $fillable = [
        'activity_id',
        'file_path',
        'process_status',
        'started_at',
        'completed_at',
        'total_records',
        'valid_records',
        'invalid_records',
        'successful_records'
    ];
}

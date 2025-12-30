<?php

namespace App\Jobs;

use App\Models\BulkCreditBatch;
use App\Models\BulkCreditItem;
use App\Models\CreditActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class RetryFinalizeJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $activityId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {

            $success = BulkCreditItem::where('activity_id', $this->activityId)
                ->where('status', 'SUCCESS')
                ->count();

            $failed = BulkCreditItem::where('activity_id', $this->activityId)
                ->where('status', 'FAILED')
                ->count();

            BulkCreditBatch::where('activity_id', $this->activityId)->update([
                'status' => $failed > 0 ? 'PARTIALLY_COMPLETED' : 'COMPLETED',
            ]);

            CreditActivityLog::where('activity_id', $this->activityId)->update([
                'process_status' => $failed > 0 ? 'PARTIALLY_COMPLETED' : 'COMPLETED',
                'successful_records' => $success,
                'failed_records' => $failed,
                'completed_at' => now(),
            ]);
        });
    }
}

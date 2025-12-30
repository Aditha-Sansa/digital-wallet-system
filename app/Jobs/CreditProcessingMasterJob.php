<?php

namespace App\Jobs;

use App\Models\BulkCreditBatch;
use App\Models\CreditActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class CreditProcessingMasterJob implements ShouldQueue
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
            $activity = CreditActivityLog::where('activity_id', $this->activityId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($activity->process_status !== 'UPLOADED') {
                return;
            }

            $activity->update([
                'process_status' => 'PROCESSING',
                'started_at' => now(),
            ]);

            $csv = Reader::createFromPath(
                Storage::path($activity->file_path),
                'r'
            );
            $csv->setHeaderOffset(0);
            $csv->setDelimiter(',');

            $chunkSize = 1000;
            $buffer = [];
            $totalRecords = 0;
            $totalChunks = 0;

            foreach ($csv->getRecords() as $row) {
                $buffer[] = $row;
                $totalRecords++;

                if (count($buffer) === $chunkSize) {
                    $totalChunks++;
                    $buffer = [];
                }
            }

            if (! empty($buffer)) {
                $totalChunks++;
            }

            BulkCreditBatch::create([
                'activity_id' => $this->activityId,
                'total_chunks' => $totalChunks,
                'processed_chunks' => 0,
                'successful_chunks' => 0,
                'failed_chunks' => 0,
                'status' => 'PROCESSING',
            ]);

            $activity->update([
                'total_records' => $totalRecords,
            ]);
        });

        $this->dispatchChunks();
    }

    protected function dispatchChunks(): void
    {
        $activity = CreditActivityLog::where('activity_id', $this->activityId)->firstOrFail();

        $csv = Reader::createFromPath(
            Storage::path($activity->file_path),
            'r'
        );

        $csv->setHeaderOffset(0);
        $csv->setDelimiter(',');

        $chunkSize = 1000;
        $buffer = [];

        foreach ($csv->getRecords() as $row) {
            $buffer[] = $row;

            if (count($buffer) === $chunkSize) {
                BulkCreditChunkJob::dispatch(
                    $this->activityId,
                    $buffer
                )->onQueue('bulk-chunks');

                $buffer = [];
            }
        }

        if (! empty($buffer)) {
            BulkCreditChunkJob::dispatch(
                $this->activityId,
                $buffer
            )->onQueue('bulk-chunks');
        }
    }
}

<?php

namespace App\Jobs;

use App\Models\BulkCreditItem;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Throwable;

class RetryFailedCreditItemsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $activityId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $jobs = [];

        BulkCreditItem::where('activity_id', $this->activityId)
            ->where('status', 'FAILED')
            ->orderBy('id')
            ->chunk(500, function ($items) use (&$jobs) {
                $jobs[] = new RetryCreditChunkJob(
                    $this->activityId,
                    $items->pluck('id')->toArray()
                );
            });

        $activityId = $this->activityId;

        Bus::batch($jobs)
            ->name("Retry Bulk Credit: {$activityId}")
            ->onQueue('bulk-chunks')
            ->then(fn (Batch $batch) => RetryFinalizeJob::dispatch($activityId))
            ->catch(fn (Batch $batch, Throwable $e) => RetryFinalizeJob::dispatch($activityId))
            ->dispatch();
    }
}

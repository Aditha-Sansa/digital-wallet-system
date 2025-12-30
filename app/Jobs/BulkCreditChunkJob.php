<?php

namespace App\Jobs;

use App\DTOs\BulkCreditRowDTO;
use App\Models\BulkCreditBatch;
use App\Models\BulkCreditItem;
use App\Models\CreditActivityLog;
use App\Models\WalletLedgerTransaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class BulkCreditChunkJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $activityId,
        public array $rows
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $successCount = 0;
        $failureCount = 0;

        foreach ($this->rows as $row) {
            try {
                $dto = BulkCreditRowDTO::fromArray($row);

                $rowHash = hash(
                    'sha256',
                    $this->activityId.$dto->uuid.number_format($dto->amount, 2, '.', '')
                );

                $item = BulkCreditItem::firstOrCreate(
                    [
                        'activity_id' => $this->activityId,
                        'row_hash' => $rowHash,
                    ],
                    [
                        'user_id' => $dto->uuid,
                        'amount' => $dto->amount,
                        'status' => 'PENDING',
                    ]
                );

                if ($item->status === 'SUCCESS') {
                    $successCount++;

                    continue;
                }

                DB::transaction(function () use ($item) {
                    WalletLedgerTransaction::create([
                        'user_id' => $item->user_id,
                        'amount' => $item->amount,
                        'type' => 'CREDIT',
                        'source' => 'Credit Campaigne',
                        'reference_id' => $this->activityId,
                        'idempotency_key' => hash(
                            'sha256',
                            $this->activityId.$item->id
                        ),
                    ]);

                    $item->update(['status' => 'SUCCESS']);
                });

                $successCount++;

            } catch (Throwable $e) {
                report($e);
                $failureCount++;

                BulkCreditItem::updateOrCreate(
                    [
                        'activity_id' => $this->activityId,
                        'row_hash' => $rowHash ?? Str::uuid(),
                    ],
                    [
                        'status' => 'FAILED',
                        'error_info' => $e->getMessage(),
                    ]
                );
            }
        }

        $this->reportChunkOutcome($successCount, $failureCount);
    }

    protected function reportChunkOutcome(int $success, int $failed): void
    {
        DB::transaction(function () use ($failed) {

            $batch = BulkCreditBatch::where('activity_id', $this->activityId)
                ->lockForUpdate()
                ->firstOrFail();

            $batch->increment('processed_chunks');

            if ($failed === 0) {
                $batch->increment('successful_chunks');
            } else {
                $batch->increment('failed_chunks');
            }

            if ($batch->processed_chunks >= $batch->total_chunks) {
                $this->finalizeBatch($batch);
            }
        });
    }

    protected function finalizeBatch(BulkCreditBatch $batch): void
    {
        if ($batch->failed_chunks === 0) {
            $batch->update(['status' => 'COMPLETED']);
            $activityStatus = 'COMPLETED';
        } elseif ($batch->successful_chunks > 0) {
            $batch->update(['status' => 'PARTIALLY_COMPLETED']);
            $activityStatus = 'PARTIALLY_COMPLETED';
        } else {
            $batch->update(['status' => 'FAILED']);
            $activityStatus = 'FAILED';
        }

        CreditActivityLog::where('activity_id', $batch->activity_id)
            ->update([
                'process_status' => $activityStatus,
                'completed_at' => now(),
            ]);
    }
}

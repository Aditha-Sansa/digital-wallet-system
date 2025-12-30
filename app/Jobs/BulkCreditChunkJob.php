<?php

namespace App\Jobs;

use App\DTOs\BulkCreditRowDTO;
use App\Models\BulkCreditBatch;
use App\Models\BulkCreditItem;
use App\Models\CreditActivityLog;
use App\Models\User;
use App\Models\WalletLedgerTransaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
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
        $invalidCount = 0;

        foreach ($this->rows as $row) {
            try {
                $dto = BulkCreditRowDTO::fromArray($row);

            } catch (\Throwable $th) {

                $invalidCount++;

                // Need a better way to log this concern in production
                Log::error([
                    'reason' => $th->getMessage(),
                    'affected_record' => $row,
                ]);

                continue;
            }

            if (! $this->userExists($dto->uuid)) {
                $invalidCount++;

                Log::error([
                    'reason' => "The user with uuid: {$dto->uuid} doesn't exist.",
                    'affected_record' => $row,
                ]);

                continue;
            }

            $rowHash = hash(
                'sha256',
                $this->activityId.$dto->uuid.number_format($dto->amount, 2, '.', '')
            );

            try {
                // This is for testing failed credit records.
                if (in_array($dto->uuid, config('bulk_credit.fail_user_ids'), true)) {
                    throw new RuntimeException('Emulating failure for testing');
                }

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

            } catch (QueryException|Throwable|RuntimeException $e) {

                BulkCreditItem::updateOrCreate(
                    [
                        'activity_id' => $this->activityId,
                        'row_hash' => $rowHash,
                    ],
                    [
                        'user_id' => $dto->uuid,
                        'amount' => $dto->amount,
                        'status' => 'FAILED',
                        'error_info' => $e->getMessage(),
                    ]
                );

                $failureCount++;
            }
        }

        $this->reportChunkOutcome($successCount, $failureCount, $invalidCount);
    }

    protected function reportChunkOutcome(int $success, int $failed, int $invalid): void
    {
        DB::transaction(function () use ($success, $failed, $invalid) {

            $batch = BulkCreditBatch::where('activity_id', $this->activityId)
                ->lockForUpdate()
                ->firstOrFail();

            $batch->increment('processed_chunks');

            if ($failed === 0) {
                $batch->increment('successful_chunks');
            } else {
                $batch->increment('failed_chunks');
            }

            CreditActivityLog::where('activity_id', $batch->activity_id)
                ->update([
                    'invalid_records' => DB::raw("invalid_records + {$invalid}"),
                    'failed_records' => DB::raw("failed_records + {$failed}"),
                    'successful_records' => DB::raw("successful_records + {$success}"),
                    'completed_at' => now(),
                ]);

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

    protected function userExists(string $userId): bool
    {
        $value = Cache::get("user_uuid_exists:{$userId}", function () use ($userId) {
            return User::where('uuid', $userId)->exists();
        });

        return $value;
    }
}

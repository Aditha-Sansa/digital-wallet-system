<?php

namespace App\Jobs;

use App\Models\BulkCreditItem;
use App\Models\WalletLedgerTransaction;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class RetryCreditChunkJob implements ShouldQueue
{
    use Batchable, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $activityId,
        public array $itemIds
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $successCount = 0;
        $failureCount = 0;

        foreach ($this->itemIds as $itemId) {
            $item = BulkCreditItem::find($itemId);

            if (! $item || $item->status === 'SUCCESS') {
                continue;
            }

            try {

                if (in_array($item->user_id, config('bulk_credit.fail_user_ids'), true)) {
                    throw new RuntimeException('Emulating failure for testing');
                }

                DB::transaction(function () use ($item) {
                    WalletLedgerTransaction::firstOrCreate(
                        [
                            'idempotency_key' => hash(
                                'sha256',
                                $this->activityId.$item->id
                            ),
                        ],
                        [
                            'user_id' => $item->user_id,
                            'amount' => $item->amount,
                            'source' => 'Credit Campaigne',
                            'reference_id' => $this->activityId,
                            'type' => 'CREDIT',
                        ]
                    );

                    $item->update([
                        'status' => 'SUCCESS',
                        'error_info' => null,
                    ]);
                });

                $successCount++;
            } catch (Throwable|RuntimeException $e) {
                $item->update([
                    'error_info' => $e->getMessage(),
                ]);

                report($e);

                $failureCount++;
            }
        }
    }
}

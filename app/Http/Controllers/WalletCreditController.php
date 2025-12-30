<?php

namespace App\Http\Controllers;

use App\Events\NewCreditActivityCreated;
use App\Jobs\RetryFailedCreditItemsJob;
use App\Models\CreditActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WalletCreditController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $activityId = Str::uuid()->toString();

        $path = $request->file('file')->storeAs(
            "bulk-credits/{$activityId}",
            'credits.csv',
            'local'
        );

        CreditActivityLog::create([
            'activity_id' => $activityId,
            'file_path' => $path,
            'process_status' => 'UPLOADED',
        ]);

        event(new NewCreditActivityCreated($activityId));

        return response()->json([
            'activity_id' => $activityId,
            'status' => 'accepted',
        ], 202);
    }

    public function retry(Request $request)
    {
        $validated = $request->validate([
            'activity_id' => ['required', 'uuid'],
        ]);

        $activityLog = CreditActivityLog::where('activity_id', $validated['activity_id'])->firstOrFail();

        if (! in_array($activityLog->process_status, ['PARTIALLY_COMPLETED', 'FAILED'])) {
            return response()->json(['status' => 'Activity is not retryable'], 400);
        }

        RetryFailedCreditItemsJob::dispatch($activityLog->activity_id)->onQueue('bulk-main-queue');

        $activityLog->update([
            'process_status' => 'PROCESSING',
        ]);

        return response()->json(['status' => 'Retry initiated for this credit activity'], 202);
    }
}

<?php

namespace App\Http\Controllers;

use App\Events\NewCreditActivityCreated;
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
}

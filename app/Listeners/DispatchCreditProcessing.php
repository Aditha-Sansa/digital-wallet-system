<?php

namespace App\Listeners;

use App\Events\NewCreditActivityCreated;
use App\Jobs\CreditProcessingMasterJob;

class DispatchCreditProcessing
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(NewCreditActivityCreated $event): void
    {
        CreditProcessingMasterJob::dispatch($event->activityId)->onQueue('bulk-main-queue');
    }
}

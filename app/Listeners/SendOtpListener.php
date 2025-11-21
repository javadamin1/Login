<?php

namespace App\Listeners;

use App\Jobs\SendOtpJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendOtpListener {
    /**
     * Create the event listener.
     */
    public function __construct () {
        //
    }

    /**
     * Handle the event.
     */
    public function handle (object $event) : void {
        $otp = $event->otp;

        if ( !$otp->user || !$otp->user->phone ) {
            return;
        }

        // Dispatch Job (async)
        SendOtpJob::dispatch($otp->user->phone, $otp->code);
    }
}

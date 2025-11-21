<?php

namespace App\Events;

use App\Models\OTP;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OtpGenerated {
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct (public OTP $otp) {
    }

}

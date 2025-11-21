<?php

namespace App\Jobs;

use App\Services\SmsManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOtpJob implements ShouldQueue {
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct (
        protected string $phone, protected string $code
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle (SmsManager $sms) : void {
        $sms->send($this->phone, "کد تأیید شما: {$this->code}");
    }

    public function failed (Throwable $exception) : void {
        Log::error('SendOtpJob failed', [
            'phone' => $this->phone,
            'code'  => $this->code,
            'error' => $exception->getMessage(),
        ]);
    }
}

<?php

namespace App\Providers;

use App\Events\OtpGenerated;
use App\Listeners\SendOtpListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider {
    protected $listen = [
        OtpGenerated::class => [
            SendOtpListener::class,
        ],
    ];
}

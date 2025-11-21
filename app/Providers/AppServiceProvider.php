<?php

namespace App\Providers;

use App\Contracts\OtpRepositoryInterface;
use App\Repositories\DatabaseOtpRepository;
use App\Repositories\RedisOtpRepository;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register () : void {
        $this->app->bind(OtpRepositoryInterface::class, function ($app) {
            try {
                Redis::connection()->ping();

                return new RedisOtpRepository();
            } catch ( \Exception $e ) {
                return new DatabaseOtpRepository();
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot () : void {
        //
    }
}

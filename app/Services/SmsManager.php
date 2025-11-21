<?php

namespace App\Services;

use HoomanMirghasemi\Sms\Facades\Sms;
use Illuminate\Support\Facades\Log;
use Throwable;

class SmsManager {
    public function send (string $to, string $message, array $options = []) : bool {
        // use fake code in develop mode
        if ( app()->environment(['local', 'develop', 'testing']) ) {
            return $this->sendWithDriver('fake', $to, $message, $options);
        }

        $default = config('sms.driver');
        $order   = config('sms.fallback_order', [$default]);

        if ( empty($order) ) {
            Log::channel('sms')->error('Sms api not found');
        }

        if ( !empty($default) && ( !in_array($default, $order) || $order[0] !== $default) ) {
            array_unshift($order, $default);
        }
        $order         = array_unique($order);
        $lastException = null;
        foreach ( $order as $driver ) {

            try {
                $resp = $this->sendWithDriver($driver, $to, $message, $options);
                if ( $resp ) {
                    return true;
                }
            } catch ( Throwable $e ) {
                Log::channel('sms')->warning("SMS driver [$driver] failed: " . $e->getMessage(), [
                    'driver'  => $driver,
                    'to'      => $to,
                    'message' => $message
                ]);
                $lastException = $e;
            }
        }

        Log::error('All SMS drivers failed', [
            'to'         => $to,
            'message'    => $message,
            'last_error' => $lastException ? $lastException->getMessage() : null,
        ]);

        return false;
    }

    protected function sendWithDriver (string $driver, string $to, string $message, array $options = []) : bool {

        $sms = Sms::driver($driver)->to($to)->message($message);

        if ( !empty($options['from']) ) {
            $sms->from($options['from']);
        }
        $result = $sms->send();
        if ( is_bool($result) ) {
            return $result;
        }
        if ( is_array($result) ) {
            return $result['success'] ?? true;
        }

        return (bool) $result;
    }
}

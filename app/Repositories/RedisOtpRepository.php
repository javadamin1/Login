<?php

namespace App\Repositories;

use App\Contracts\OtpRepositoryInterface;
use App\Models\OTP;
use Illuminate\Support\Facades\Redis;

class RedisOtpRepository implements OtpRepositoryInterface {
    protected string $prefix = 'otp:';

    protected function key (int $userId, string $type) : string {
        return "{$this->prefix}{$type}:{$userId}";
    }

    public function create (int $userId, string $code, string $type, int $ttl) : OTP {
        $key  = $this->key($userId, $type);
        $data = json_encode([
            'user_id'    => $userId,
            'code'       => $code,
            'type'       => $type,
            'expires_at' => now()->addSeconds($ttl)->toDateTimeString(),
        ], JSON_THROW_ON_ERROR);

        Redis::setex($key, $ttl, $data);

        return new OTP([
            'user_id'    => $userId,
            'code'       => $code,
            'type'       => $type,
            'expires_at' => now()->addSeconds($ttl),
        ]);
    }

    public function find (int $userId, string $type) : ?OTP {
        $key  = $this->key($userId, $type);
        $data = Redis::get($key);

        if ( !$data ) {
            return null;
        }

        $decoded = json_decode($data, true);

        return new OTP($decoded);
    }

    public function delete (int $userId, string $type) : void {
        Redis::del($this->key($userId, $type));
    }
}

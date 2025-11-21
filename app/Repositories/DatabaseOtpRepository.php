<?php

namespace App\Repositories;

use App\Contracts\OtpRepositoryInterface;
use App\Models\OTP;

class DatabaseOtpRepository implements OtpRepositoryInterface {
    public function create (int $userId, string $code, string $type, int $ttl) : OTP {
        return OTP::updateOrCreate(['user_id' => $userId, 'type' => $type], [
            'code'       => $code,
            'expires_at' => now()->addSeconds($ttl),
        ]);
    }

    public function find (int $userId, string $type) : ?OTP {
        return OTP::where('user_id', $userId)->where('type', $type)->where('expires_at', '>', now())->first();
    }

    public function delete (int $userId, string $type) : void {
        OTP::where('user_id', $userId)->where('type', $type)->delete();
    }
}

<?php

namespace App\Repositories;

use App\Contracts\OtpRepositoryInterface;
use App\Enums\OTP\Type;
use App\Models\OTP;

class DatabaseOtpRepository implements OtpRepositoryInterface {
    public function createOrUpdate (int $userId, string $code, Type $type, int $ttl) : OTP {
        return OTP::updateOrCreate(['user_id' => $userId, 'type' => $type->value], [
            'code'       => $code,
            'expires_at' => now()->addSeconds($ttl),
        ]);
    }

    public function find (int $userId, Type $type) : ?OTP {
        return OTP::where('user_id', $userId)->where('type', $type->value)->where('expires_at', '>', now())->first();
    }

    public function delete (int $userId, Type $type) : void {
        OTP::where('user_id', $userId)->where('type', $type->value)->delete();
    }


}

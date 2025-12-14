<?php

namespace App\Services;

use App\Contracts\OtpRepositoryInterface;
use App\Enums\OTP\Type;
use App\Events\OtpGenerated;
use App\Models\OTP;

class OtpService {
    public function __construct (protected OtpRepositoryInterface $repo) {
    }

    /**
     * @throws \Random\RandomException
     */
    public function generate (int $userId, Type $type, int $ttl = 300) : OTP {
        $code = (string) random_int(1000, 9999);
        $otp  = $this->repo->createOrUpdate($userId, $code, $type, $ttl);

        // Fire event
        event(new OtpGenerated($otp));

        return $otp;
    }

    public function verify (int $userId, Type $type, string $code) : bool {
        $otp = $this->find($userId, $type);

        if ( !$otp || !$otp->isValid($code) ) {
            return false;
        }

        $this->repo->delete($userId, $type);

        return true;
    }

    public function find (int $userId, Type $type) : OTP {
        return $this->repo->find($userId, $type);
    }
}

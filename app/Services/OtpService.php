<?php

namespace App\Services;

use App\Contracts\OtpRepositoryInterface;
use App\Events\OtpGenerated;
use App\Models\OTP;

class OtpService {
    public function __construct (protected OtpRepositoryInterface $repo) {
    }

    /**
     * @throws \Random\RandomException
     */
    public function generate (int $userId, string $type, int $ttl = 300) : OTP {
        $code = (string) random_int(1000, 9999);
        $otp  = $this->repo->create($userId, $code, $type, $ttl);

        // Fire event
        event(new OtpGenerated($otp));

        return $otp;
    }

    public function verify (int $userId, string $type, string $code) : bool {
        $otp = $this->repo->find($userId, $type);

        if ( !$otp || !$otp->isValid($code) ) {
            return false;
        }

        $this->repo->delete($userId, $type);

        return true;
    }
}

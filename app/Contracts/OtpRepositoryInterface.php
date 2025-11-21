<?php

namespace App\Contracts;

use App\Models\OTP;

interface OtpRepositoryInterface {

    public function create (int $userId, string $code, string $type, int $ttl) : OTP;

    public function find (int $userId, string $type) : ?OTP;

    public function delete (int $userId, string $type) : void;
}

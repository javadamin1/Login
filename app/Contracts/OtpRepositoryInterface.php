<?php

namespace App\Contracts;

use App\Enums\OTP\Type;
use App\Models\OTP;

interface OtpRepositoryInterface {

    public function createOrUpdate (int $userId, string $code, Type $type, int $ttl) : OTP;

    public function find (int $userId, Type $type) : ?OTP;

    public function delete (int $userId, Type $type) : void;
}

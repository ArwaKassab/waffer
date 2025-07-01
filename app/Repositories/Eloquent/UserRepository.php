<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    public function create(array $data)
    {
        return User::create($data);
    }

    public function findByPhoneAndType(string $phone)
    {
        $processedPhone = $this->processPhoneNumber($phone);

        return User::where('phone', $processedPhone)
            ->first();
    }
    public function processPhoneNumber(string $phone): string
    {
        if (preg_match('/^0\d{9}$/', $phone)) {
            return '00963' . substr($phone, 1);
        }
        return $phone;
    }


}

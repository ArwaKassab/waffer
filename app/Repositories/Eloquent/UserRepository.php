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

    public function findByPhoneAndType(string $phone, string $type)
    {
        return User::where('phone', $phone)->where('type', $type)->first();
    }
}

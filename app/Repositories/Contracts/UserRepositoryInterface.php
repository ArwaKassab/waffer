<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function create(array $data);
//    public function findByPhoneAndType(string $phone);
    public function findByPhoneAndType(string $phone, string $type);
    public function findByUserNameAndType(string $userName, string $type);


}

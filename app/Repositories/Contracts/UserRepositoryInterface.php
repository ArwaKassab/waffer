<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function create(array $data);
    public function findByPhoneAndType(string $phone);

}

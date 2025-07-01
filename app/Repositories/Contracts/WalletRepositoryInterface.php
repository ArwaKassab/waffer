<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface WalletRepositoryInterface
{
    public function getBalance(User $user): float;
}

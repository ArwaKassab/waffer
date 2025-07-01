<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\WalletRepositoryInterface;

class WalletRepository implements WalletRepositoryInterface
{
    public function getBalance(User $user): float
    {
        return (float) $user->wallet_balance;
    }
}

<?php

namespace App\Services;

use App\Models\User;

class WalletService
{
    public function hasSufficientBalance(User $user, float $amount): bool
    {
        return $user->wallet_balance >= $amount;
    }

    public function deduct(User $user, float $amount): void
    {
        $user->wallet_balance -= $amount;
        $user->save();
    }
}

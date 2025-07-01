<?php

namespace App\Services;
use App\Repositories\Contracts\WalletRepositoryInterface;
use App\Models\User;

class WalletService
{
    protected $walletRepo;

    public function __construct(WalletRepositoryInterface $walletRepo)
    {
        $this->walletRepo = $walletRepo;
    }

    public function hasSufficientBalance(User $user, float $amount): bool
    {
        return $user->wallet_balance >= $amount;
    }

    public function deduct(User $user, float $amount): void
    {
        $user->wallet_balance -= $amount;
        $user->save();
    }

    public function getBalance(User $user): float
    {
        return $this->walletRepo->getBalance($user);
    }
}

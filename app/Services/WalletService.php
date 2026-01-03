<?php

namespace App\Services;

use App\Repositories\Contracts\WalletRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletService
{
    protected WalletRepositoryInterface $walletRepo;

    public function __construct(WalletRepositoryInterface $walletRepo)
    {
        $this->walletRepo = $walletRepo;
    }

    /**
     * التحقق والخصم من المحفظة داخل معاملة
     */
    public function deductLocked(User $user, float $amount): void
    {
        if ($user->wallet_balance < $amount) {
            throw ValidationException::withMessages(['wallet' => 'الرصيد غير كافٍ.']);
        }

        $user->wallet_balance -= $amount;
        $user->save();
    }



    /**
     * التحقق فقط من كفاية الرصيد دون خصم
     */
    public function hasSufficientBalance(User $user, float $amount): bool
    {
        return $user->wallet_balance >= $amount;
    }

    /**
     * إرجاع الرصيد الحالي
     */
    public function getBalance(User $user): float
    {
        return $this->walletRepo->getBalance($user);
    }
}

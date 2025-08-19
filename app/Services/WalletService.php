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
    public function deduct(User $user, float $amount): void
    {
        $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

        if ($lockedUser->wallet_balance < $amount) {
            throw ValidationException::withMessages(['wallet' => 'الرصيد غير كافٍ.']);
        }

        $lockedUser->wallet_balance -= $amount;
        $lockedUser->save();
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

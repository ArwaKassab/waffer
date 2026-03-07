<?php

// app/Services/SubAdminUserService.php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Events\UserBanned;
use App\Events\UserUnbanned;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CustomerService
{
    public function __construct(private CustomerRepositoryInterface $customerRepo) {}

    public function listCustomersForSubAdminAreaPaginated(int $areaId, int $perPage): LengthAwarePaginator
    {
        return $this->customerRepo->getCustomersByAreaIdPaginated($areaId, $perPage);
    }

    /**
     * يعيد عناوين زبون معيّن بشرط أن يكون ضمن نفس منطقة الـSub Admin
     */
    public function listCustomerAddressesForSubAdminNoPaginate(
        int $customerId,
        int $subAdminAreaId,
    ): Collection {
        return $this->customerRepo
            ->getUserAddresses($customerId, $subAdminAreaId);
    }

    public function listBannedCustomersForSubAdminPaginated(
        int $subAdminAreaId
    ): LengthAwarePaginator {
        $perPage = 10; // ثابت
        return $this->customerRepo->getBannedCustomersPaginated($subAdminAreaId, $perPage);
    }

    /**
     * يعيّن حالة الحظر أو يقلبها (toggle) إن لم تُمرَّر قيمة صريحة.
     *
     * @param  int        $customerId
     * @param  int        $areaId
     * @param  bool|null  $desired  null => toggle, otherwise set to given bool
     * @param  string|null $reason  سبب الحظر (اختياري)
     * @return array{0: bool $changed, 1: bool $nowBanned}
     */

    public function setOrToggleBanInArea(
        int $customerId,
        int $areaId,
        ?bool $desired = null,
        ?string $reason = null
    ): array {
        return DB::transaction(function () use ($customerId, $areaId, $desired, $reason) {
            $user = $this->customerRepo->findCustomerInAreaOrFail($customerId, $areaId);

            $target = is_null($desired) ? !$user->is_banned : $desired;

            if ($user->is_banned === $target) {
                return [false, $user->is_banned];
            }

            $this->customerRepo->setBanned($user->id, $target);

            if ($target) {
                event(new UserBanned($user, $reason));
            } else {
                event(new UserUnbanned($user));
            }

            return [true, $target];
        });
    }

    public function getCustomersCountByBanStatus(int $areaId): array
    {
        return $this->customerRepo->countCustomersByArea($areaId);
    }

    public function getTotalWalletBalanceByArea(int $areaId): float
    {
        return Cache::rememberForever(
            "area:{$areaId}:customers_wallet_total",
            fn () => $this->customerRepo->sumCustomerWalletsByArea($areaId)
        );
    }

    public function getCustomersWithBalanceCountByArea(int $areaId): int
    {
        return Cache::rememberForever(
            "area:{$areaId}:customers_wallet_with_balance_count",
            fn () => $this->customerRepo->countCustomersWithWalletBalanceByArea($areaId)
        );
    }

    public function getCustomersWithBalanceByArea(int $areaId, int $perPage): LengthAwarePaginator
    {
        return $this->customerRepo->paginateCustomersWithWalletBalanceByArea($areaId, $perPage);
    }
    public function topUpCustomerWallet(int $customerId, float $amount): User
    {
        return DB::transaction(function () use ($customerId, $amount) {

            $user = $this->customerRepo->findCustomerByIdForUpdate($customerId);

            if ($user->is_banned) {
                abort(403, 'لا يمكن شحن محفظة زبون محظور.');
            }

            return $this->customerRepo->incrementWalletBalance($user, $amount);
        });
    }
    public function updateCustomerWalletBalance(int $customerId, float $newBalance): User
    {
        return DB::transaction(function () use ($customerId, $newBalance) {

            $user = $this->customerRepo->findCustomerByIdForUpdate($customerId);

            if ($user->is_banned) {
                abort(403, 'لا يمكن تعديل محفظة زبون محظور.');
            }

            return $this->customerRepo->setWalletBalance($user, $newBalance);
        });

    }
}

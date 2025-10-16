<?php

// app/Services/SubAdminUserService.php
namespace App\Services;

use App\Events\UserBanned;
use App\Events\UserUnbanned;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
     * @param int $customerId
     * @param int $areaId
     * @param bool|null $desired  null => toggle, otherwise set to given bool
     * @return array{0: bool $changed, 1: bool $nowBanned}
     */
    public function setOrToggleBanInArea(
        int $customerId,
        int $areaId,
        ?bool $desired = null,
    ): array {
        return DB::transaction(function () use ($customerId, $areaId, $desired) {
            $user = $this->customerRepo->findCustomerInAreaOrFail($customerId, $areaId);

            $target = is_null($desired) ? !$user->is_banned : $desired;

            if ($user->is_banned === $target) {
                return [false, $user->is_banned];
            }

            $this->customerRepo->setBanned($user->id, $target);

            // أطلق الحدث الصحيح
            if ($target) {
                event(new UserBanned($user));
            } else {
                event(new UserUnbanned($user));
            }

            return [true, $target];
        });
    }


}

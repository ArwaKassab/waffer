<?php

// app/Repositories/EloquentUserRepository.php
namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRepository implements CustomerRepositoryInterface
{
    /** القاعدة المشتركة لكل استعلامات العملاء ضمن منطقة معيّنة */
    public function baseQuery(int $areaId): Builder
    {
        return User::query()
            ->where('area_id', $areaId)
            ->where('type', 'customer')
            ->with(['addresses'])
            ->withCount('orders')
            ->select(['id','name','phone','wallet_balance','is_banned','area_id','type']);
    }


    public function getCustomersByAreaIdPaginated(int $areaId, int $perPage): LengthAwarePaginator
    {
        return $this->baseQuery($areaId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * يعيد عناوين مستخدم مع التحقق أنه زبون وبنفس الـarea
     */

    public function getUserAddresses(
        int $userId,
        int $areaId,
        bool $includeDeleted = false
    ): Collection {
        $user = User::query()
            ->whereKey($userId)
            ->where('area_id', $areaId)
            ->where('type', 'customer')
            ->first();

        if (!$user) {
            throw new ModelNotFoundException('العميل غير موجود ضمن منطقتك.');
        }

        $q = $user->addresses()
            ->when($includeDeleted, fn($q) => $q->withTrashed())
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->select([
                'id','user_id','title','address_details','latitude','longitude','is_default','area_id'
            ]);

        return $q->get();
    }

    public function getBannedCustomersPaginated(
        int $areaId,
        int $perPage = 10
    ):LengthAwarePaginator {
        return $this->baseQuery($areaId)
            ->where('is_banned', true)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findCustomerInAreaOrFail(int $userId, int $areaId): User
    {
        /** @var User|null $user */
        $user = User::query()
            ->whereKey($userId)
            ->where('area_id', $areaId)
            ->where('type', 'customer')
            ->first();
        if (!$user) {
            throw new ModelNotFoundException('العميل غير موجود ضمن منطقتك.');
        }

        return $user;
    }

    public function setBanned(int $userId, bool $banned): void
    {
        $data = ['is_banned' => $banned];


        User::whereKey($userId)->update($data);
    }

}

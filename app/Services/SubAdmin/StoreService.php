<?php

namespace App\Services\SubAdmin;

use App\Models\User;
use App\Repositories\Contracts\StoreRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreService
{
    public function __construct(
        protected StoreRepositoryInterface $storeRepository
    ) {
    }

    /**
     * جلب المتاجر في نفس منطقة الأدمن الحالي.
     */
    public function getStoresForCurrentAdminArea(Request $request,int $perPage = 20)
    {

        $areaId = (int) $request->area_id;

        return $this->storeRepository->getStoresByAreaForAdmin($areaId, $perPage);
    }


    /**
     * حذف متجر معيّن (Soft Delete) ضمن منطقة معيّنة للأدمن.
     */
    public function deleteStoreForAdmin(Request $request, int $storeId): bool
    {
        $areaId = (int) $request->area_id;

        return $this->storeRepository->deleteStoreByIdForAdmin($storeId, $areaId);
    }

}

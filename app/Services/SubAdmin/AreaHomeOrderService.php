<?php

namespace App\Services\SubAdmin;

use App\Models\Category;
use App\Models\User;
use App\Repositories\Eloquent\AreaHomeOrderRepository;
use Illuminate\Support\Facades\DB;

class AreaHomeOrderService
{
    public function __construct(protected AreaHomeOrderRepository $repo) {}

    /**
     * ترتيب التصنيفات لمنطقة
     * - تحقق: IDs موجودة في categories
     * - (اختياري مُستحسن): التصنيفات مرتبطة بالمنطقة في area_category_pivot
     */
    public function setCategoryOrder(int $areaId, array $categoryIds): void
    {
        DB::transaction(function () use ($areaId, $categoryIds) {

            // 1) exists:categories
            $count = Category::query()->whereIn('id', $categoryIds)->count();
            if ($count !== count($categoryIds)) {
                abort(422, 'يوجد تصنيف غير موجود ضمن القائمة.');
            }

            // 2) (مستحسن) تأكيد أن التصنيفات ضمن نفس المنطقة عبر pivot
            $countInArea = Category::query()
                ->whereIn('id', $categoryIds)
                ->whereHas('areas', fn($q) => $q->where('areas.id', $areaId))
                ->count();

            if ($countInArea !== count($categoryIds)) {
                abort(422, 'يوجد تصنيف غير مرتبط بهذه المنطقة.');
            }

            $this->repo->upsertOrder($areaId, 'category', $categoryIds);
        });
    }

    /**
     * ترتيب المتاجر لمنطقة
     * - تحقق: IDs موجودة في users
     * - type=store
     * - area_id مطابق للمنطقة
     */
    public function setStoreOrder(int $areaId, array $storeIds): void
    {
        DB::transaction(function () use ($areaId, $storeIds) {

            $countStores = User::query()
                ->whereIn('id', $storeIds)
                ->where('type', 'store')
                ->count();

            if ($countStores !== count($storeIds)) {
                abort(422, 'قائمة المتاجر تحتوي على ID غير متجر.');
            }

            $countSameArea = User::query()
                ->whereIn('id', $storeIds)
                ->where('type', 'store')
                ->where('area_id', $areaId)
                ->count();

            if ($countSameArea !== count($storeIds)) {
                abort(422, 'يوجد متجر خارج هذه المنطقة ضمن القائمة.');
            }

            $this->repo->upsertOrder($areaId, 'store', $storeIds);
        });
    }

    public function toggleCategory(int $areaId, int $categoryId, bool $isActive): void
    {
        // exists + مرتبط بالمنطقة
        $ok = Category::query()
            ->whereKey($categoryId)
            ->whereHas('areas', fn($q) => $q->where('areas.id', $areaId))
            ->exists();

        if (! $ok) {
            abort(422, 'التصنيف غير موجود أو غير مرتبط بهذه المنطقة.');
        }

        $this->repo->toggle($areaId, 'category', $categoryId, $isActive);
    }

    public function toggleStore(int $areaId, int $storeId, bool $isActive): void
    {
        $ok = User::query()
            ->whereKey($storeId)
            ->where('type', 'store')
            ->where('area_id', $areaId)
            ->exists();

        if (! $ok) {
            abort(422, 'المتجر غير موجود أو ليس ضمن هذه المنطقة.');
        }

        $this->repo->toggle($areaId, 'store', $storeId, $isActive);
    }
    public function addCategoryToEnd(int $areaId, int $categoryId): void
    {
        $this->repo->addToEndIfMissing($areaId, 'category', $categoryId);
    }

    public function addStoreToEnd(int $areaId, int $storeId): void
    {
        $this->repo->addToEndIfMissing($areaId, 'store', $storeId);
    }
}

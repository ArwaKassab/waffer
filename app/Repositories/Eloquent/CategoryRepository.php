<?php

namespace App\Repositories\Eloquent;

use App\Models\Area;
use App\Models\AreaHomeOrder;
use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return Category::query()
            ->select(['id', 'name', 'image', 'created_at'])
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function allByArea(int $areaId): Collection
    {
        return Category::query()
            ->select(['id', 'name', 'image'])
            ->whereHas('areas', function ($q) use ($areaId) {
                $q->where('areas.id', $areaId);
            })
            ->orderBy('name')
            ->get();
    }

    public function paginateByArea(int $areaId, int $perPage = 20)
    {
        return Category::query()
            ->select(['id', 'name', 'image'])
            ->whereHas('areas', function ($q) use ($areaId) {
                $q->where('areas.id', $areaId);
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function getByArea(int $areaId)
    {
        return Area::findOrFail($areaId)
            ->categories()
            ->get();
    }



    public function findById(int $id): Category
    {
        /** @var Category $category */
        $category = Category::query()
            ->whereKey($id)
            ->firstOrFail(); // ✅ يرجّع Category أكيد (أو exception)

        return $category;
    }

    public function create(array $data): Category
    {
        /** @var Category $category */
        $category = Category::create($data);

        return $category->fresh();
    }

    public function update(Category $category, array $data): Category
    {
        $category->fill($data);
        $category->save();

        return $category->fresh();
    }

    public function delete(Category $category): bool
    {
        return (bool) $category->delete();
    }

    public function getNotAssignedToArea(int $areaId): Collection
    {
        return Category::query()
            ->whereDoesntHave('areas', function ($q) use ($areaId) {
                $q->where('areas.id', $areaId);
            })
            ->orderBy('name')
            ->get();
    }

    public function create_by_super_admin(array $data): Category
    {
        return Category::create($data);
    }

    // جميع التصنيفات

    public function all(): Collection
    {
        return Category::query()
            ->select(['id', 'name', 'image'])
            ->orderBy('name')
            ->get();
    }

    public function forArea(int $areaId): Collection
    {
        // IDs حسب ترتيب الأدمن
        $orderedIds = AreaHomeOrder::query()
            ->where('area_id', $areaId)
            ->where('entity_type', 'category')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('entity_id')
            ->values();

        // كل تصنيفات المنطقة (حسب pivot)
        $allInArea = Category::query()
            ->whereHas('areas', fn($q) => $q->where('areas.id', $areaId))
            ->get()
            ->keyBy('id');

        // رجّعي المرتّب أولاً
        $sorted = $orderedIds
            ->map(fn ($id) => $allInArea->get($id))
            ->filter()
            ->values();

        // (اختياري) تصنيفات موجودة بالمنطقة لكن ما إلها ترتيب بعد → آخر شي
        $missing = $allInArea
            ->except($orderedIds->all())
            ->values()
            ->sortBy('name')  // أو بدون sort إذا بدك كما هي
            ->values();

        return $sorted->concat($missing)->values();
    }


    // تصنيفات غير مرتبطة بمنطقة معينة
    public function notForArea(int $areaId): Collection
    {
        return Category::whereDoesntHave('areas', fn($q) => $q->where('areas.id', $areaId))
            ->orderBy('name')
            ->get();
    }

    // ربط تصنيف موجود بمنطقة
    public function attachToArea(int $categoryId, int $areaId): void
    {
        $area = Area::findOrFail($areaId);
        $area->categories()->syncWithoutDetaching([$categoryId]);
    }

    // فك الربط من منطقة
    public function detachFromArea(int $categoryId, int $areaId): void
    {
        $area = Area::findOrFail($areaId);
        $area->categories()->detach($categoryId);
    }

}

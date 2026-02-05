<?php

namespace App\Services\SubAdmin;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CategoryService
{
    public function __construct(
        protected CategoryRepositoryInterface $categories
    ) {}


    public function findById(int $id): ?Category
    {
        try {
            return $this->categories->findById($id);
        } catch (ModelNotFoundException) {
            return null;
        }
    }
    public function create(array $data): Category
    {
        $category = Category::create($data);
        $category->append('image_url')->makeHidden(['image']);
        return $category;
    }

    public function createAndAttachToArea(int $areaId, array $data): Category
    {
        return DB::transaction(function () use ($areaId, $data) {

            $category = Category::create($data);

            // ربط التصنيف بالمنطقة
            $category->areas()->attach($areaId);

            // تجهيز الإخراج
            $category->append('image_url')->makeHidden(['image']);

            return $category;
        });
    }

    public function update(int $id, array $data): ?Category
    {
        try {
            $category = $this->categories->findById($id);
        } catch (ModelNotFoundException) {
            return null;
        }

        // إذا في صورة جديدة، احذفي القديمة (اختياري لكن أفضل)
        if (array_key_exists('image', $data) && !empty($data['image'])) {
            $old = $category->getRawOriginal('image'); // المسار المخزن فعلياً
            if ($old) {
                Storage::disk('public')->delete($old);
            }
        }

        $updated = $this->categories->update($category, $data);

        $updated->append('image_url')->makeHidden(['image']);
        return $updated;
    }

    public function delete(int $id): bool
    {
        try {
            $category = $this->categories->findById($id);
        } catch (ModelNotFoundException) {
            return false;
        }

        return $this->categories->delete($category);
    }

    public function addCategoryToArea(int $categoryId, int $areaId): void
    {
        $this->categories->attachToArea($categoryId, $areaId);
    }
    public function create_by_super_admin(array $data): Category
    {
        return $this->categories->create_by_super_admin($data);
    }

    // جميع التصنيفات
    public function listAll(): Collection
    {
        return $this->categories->all();
    }

    // تصنيفات مرتبطة بمنطقة
    public function listForArea(int $areaId): Collection
    {
        return $this->categories->forArea($areaId);
    }

    // تصنيفات غير مرتبطة بمنطقة
    public function listUnassignedForArea(int $areaId): Collection
    {
        return $this->categories->notForArea($areaId);
    }

    // ربط تصنيف بمنطقة
    public function assignToArea(int $categoryId, int $areaId): void
    {
        $this->categories->attachToArea($categoryId, $areaId);
    }

    // فك الربط من منطقة
    public function removeFromArea(int $categoryId, int $areaId): void
    {
        $this->categories->detachFromArea($categoryId, $areaId);
    }

}

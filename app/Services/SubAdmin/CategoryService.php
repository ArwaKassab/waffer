<?php

namespace App\Services\SubAdmin;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class CategoryService
{
    public function __construct(
        protected CategoryRepositoryInterface $categories
    ) {}

    public function listAll(
        int $areaId,
        bool $paged = true,
        int $perPage = 20
    ): LengthAwarePaginator|Collection
    {
        return $paged
            ? $this->categories->paginateByArea($areaId, $perPage)
            : $this->categories->allByArea($areaId);
    }


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
}

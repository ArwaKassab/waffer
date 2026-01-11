<?php

namespace App\Repositories\Eloquent;

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

    public function all(): Collection
    {
        return Category::query()
            ->select(['id', 'name', 'image'])
            ->orderBy('name')
            ->get();
    }
    public function getAll()
    {
        return Category::all();
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


}

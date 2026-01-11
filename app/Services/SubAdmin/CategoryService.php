<?php

namespace App\Services\SubAdmin;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class CategoryService
{
    public function __construct(
        protected CategoryRepositoryInterface $categories
    ) {}

    public function listAll(bool $paged = true, int $perPage = 20): LengthAwarePaginator|Collection
    {
        return $paged
            ? $this->categories->paginate($perPage)
            : $this->categories->all();
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
        return $this->categories->create($data);
    }

    public function update(int $id, array $data): ?Category
    {
        try {
            $category = $this->categories->findById($id);
        } catch (ModelNotFoundException) {
            return null;
        }

        return $this->categories->update($category, $data);
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

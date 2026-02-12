<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    public function getProductById($id);
    public function getStoreProducts(int $storeId, int $perPage = 10): LengthAwarePaginator;

    public function create(array $data): Product;

    public function findInAdminArea(int $productId, int $areaId): ?Product;

    public function update(Product $product, array $data): bool;

    public function delete(Product $product): bool;
}

<?php

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    public function getProductById($id);
    public function getStoreProducts(int $storeId, int $perPage = 10): LengthAwarePaginator;

}

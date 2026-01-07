<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use App\Models\User;

interface StoreRepositoryInterface
{
    public function getStoresByAreaAndCategory($areaId, $categoryId);
    public function findStoreDetailsForAdmin(int $storeId, ?int $adminAreaId = null): User;
    public function getStoreDetailsWithProductsAndDiscounts($storeId);
    public function getStoresByAreaAndCategoriesPaged(int $areaId, array $categoryIds, int $perPage = 20, string $matchMode = 'any');
    public function searchStoresAndProductsGroupedByCategories(int $areaId, array $categoryIds, string $q, ?int $productsPerStoreLimit = 10, string $matchMode = 'any');
    public function getStoresByAreaForAdmin(int $areaId, int $perPage = 20);
    public function deleteStoreByIdForAdmin(int $storeId, int $areaId): bool;
    public function createStore(array $data, array $categoryIds = []): User;
    public function updateStore(User $store, array $data, ?array $categoryIds = null): User;
    public function findForUpdate(int $id):?Product;
    public function updateStatus(Product $product, string $status): bool;
}

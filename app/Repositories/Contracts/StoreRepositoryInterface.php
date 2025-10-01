<?php

namespace App\Repositories\Contracts;

interface StoreRepositoryInterface
{
    public function getStoresByAreaAndCategory($areaId, $categoryId);
    public function getStoreDetailsWithProductsAndDiscounts($storeId);
    public function getStoresByAreaAndCategoriesPaged(int $areaId, array $categoryIds, int $perPage = 20, string $matchMode = 'any');
    public function searchStoresAndProductsGroupedByCategories(int $areaId, array $categoryIds, string $q, ?int $productsPerStoreLimit = 10, string $matchMode = 'any');

}

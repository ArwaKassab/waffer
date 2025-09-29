<?php

namespace App\Services;

use App\Models\Order;
use App\Repositories\Contracts\StoreRepositoryInterface;

class StoreService
{
    protected StoreRepositoryInterface $storeRepository;

    public function __construct(StoreRepositoryInterface $storeRepository)
    {
        $this->storeRepository = $storeRepository;
    }
    public function getStoresByArea(int $areaId, int $perPage = 20)
    {
        return $this->storeRepository->getStoresByArea($areaId, $perPage);
    }



    public function getStores($areaId, $categoryId)
    {
        return $this->storeRepository->getStoresByAreaAndCategory($areaId, $categoryId);
    }
    public function getStoreDetails($storeId)
    {
        return $this->storeRepository->getStoreDetailsWithProductsAndDiscounts($storeId);
    }

    public function searchStoresAndProductsGrouped(
        int $areaId,
        int $categoryId,
        string $q,
        ?int $productsPerStoreLimit = 10 //  منتجات المتجر
    ) {
        return $this->storeRepository->searchStoresAndProductsGrouped(
            $areaId, $categoryId, $q, $productsPerStoreLimit
        );
    }

    public function searchStoresAndProductsGroupedInArea(
        int $areaId,
        string $q,
        ?int $productsPerStoreLimit = 10
    ) {
        return $this->storeRepository->searchStoresAndProductsGroupedInArea(
            $areaId, $q, $productsPerStoreLimit
        );
    }

    public function searchStoresAndProductsGroupedUniversal(
        int $areaId,
        string $q,
        ?int $productsPerStoreLimit = 10,
        ?int $categoryId = null
    ) {
        if ($categoryId) {
            return $this->searchStoresAndProductsGrouped(
                areaId: $areaId,
                categoryId: $categoryId,
                q: $q,
                productsPerStoreLimit: $productsPerStoreLimit
            );
        }

        return $this->searchStoresAndProductsGroupedInArea(
            areaId: $areaId,
            q: $q,
            productsPerStoreLimit: $productsPerStoreLimit
        );
    }


}

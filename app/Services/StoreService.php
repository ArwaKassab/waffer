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
    public function getStoresByArea(int $areaId)
    {
        return $this->storeRepository->getStoresByArea($areaId);
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




}

<?php

namespace App\Services;

use App\Repositories\Contracts\StoreRepositoryInterface;

class StoreService
{
    protected StoreRepositoryInterface $storeRepository;

    public function __construct(StoreRepositoryInterface $storeRepository)
    {
        $this->storeRepository = $storeRepository;
    }

    public function getStores($areaId, $categoryId)
    {
        return $this->storeRepository->getStoresByAreaAndCategory($areaId, $categoryId);
    }
    public function getStoreDetails($storeId)
    {
        return $this->storeRepository->getStoreDetailsWithProductsAndDiscounts($storeId);
    }

}

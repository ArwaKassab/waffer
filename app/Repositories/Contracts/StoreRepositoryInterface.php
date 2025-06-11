<?php

namespace App\Repositories\Contracts;

interface StoreRepositoryInterface
{
    public function getStoresByAreaAndCategory($areaId, $categoryId);
    public function getStoreDetailsWithProductsAndDiscounts($storeId);

}

<?php

namespace App\Services\SubAdmin;

use App\Repositories\Eloquent\DiscountRepository;

class DiscountService
{
    public function __construct(private DiscountRepository $discountRepo) {}

    public function createDiscount(array $data)
    {
        return $this->discountRepo->createByAdmin($data);
    }

    public function updateDiscount(int $id, array $data)
    {
        return $this->discountRepo->update($id, $data);
    }

    public function deleteDiscount(int $id): bool
    {
        return $this->discountRepo->delete($id);
    }

    public function getDiscount(int $id)
    {
        return $this->discountRepo->findById($id);
    }

    public function listDiscountsByProduct(int $productId)
    {
        return $this->discountRepo->listByProduct($productId);
    }

    public function listDiscountsForAdminArea(int $areaId)
    {
        return $this->discountRepo->getDiscountsByAdminArea($areaId);
    }

    public function listDiscountsForStore(int $storeId)
    {
        return $this->discountRepo->getDiscountsByStore($storeId);
    }
}

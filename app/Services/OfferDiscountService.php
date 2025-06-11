<?php

namespace App\Services;

use App\Repositories\Eloquent\OfferDiscountRepository;

class OfferDiscountService
{
    protected $repository;

    public function __construct(OfferDiscountRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAvailableDiscountsByArea($areaId, $perPage)
    {
        return $this->repository->getActiveDiscountsByArea($areaId, $perPage);
    }

    public function getAvailableOffersByArea($areaId, $perPage)
    {
        return $this->repository->getActiveOffersByArea($areaId, $perPage);
    }

    public function getAllAvailableByArea($areaId, $perPage)
    {
        return [
            'discounts' => $this->getAvailableDiscountsByArea($areaId, $perPage),
            'offers'    => $this->getAvailableOffersByArea($areaId, $perPage),
        ];
    }
}

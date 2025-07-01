<?php

namespace App\Repositories\Contracts;

interface OfferDiscountRepositoryInterface
{
    /**
     * Get active discounts filtered by area.
     *
     * @param int $areaId
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getActiveDiscountsByArea($areaId, $perPage = 10);

    /**
     * Get active offers filtered by area.
     *
     * @param int $areaId
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getActiveOffersByArea($areaId, $perPage = 10);
}

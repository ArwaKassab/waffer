<?php

namespace App\Repositories\Eloquent;

use App\Models\Offer;
use App\Models\Discount;
use Carbon\Carbon;

class OfferDiscountRepository
{
    public function getActiveDiscountsByArea($areaId, $perPage = 10)
    {
        return Discount::with(['product:id,name,image,store_id', 'product.store:id,name,area_id,image'])
            ->where('status', 'active')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->whereHas('product.store', fn ($q) => $q->where('area_id', $areaId))
            ->orderByDesc('id')
            ->paginate($perPage);

    }

    public function getActiveOffersByArea($areaId, $perPage = 10)
    {
        return Offer::where('status', 'active')
            ->whereDate('start_date', '<=', Carbon::today())
            ->whereDate('end_date', '>=', Carbon::today())
            ->whereHas('products.store', function ($q) use ($areaId) {
                $q->where('area_id', $areaId);
            })
            ->paginate($perPage);
    }
}

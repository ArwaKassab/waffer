<?php

namespace App\Repositories\Eloquent;

use App\Models\Offer;
use App\Models\Discount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OfferDiscountRepository
{
    public function getActiveDiscountsByArea(int $areaId, int $perPage = 10)
    {
        $today = Carbon::now(config('app.timezone'))->toDateString();

        $paginator = Discount::query()
            ->with([
                'product:id,name,image,store_id,details,price,status,quantity,unit',
                'product.store:id,name,area_id,image,open_hour,close_hour,status',
            ])
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->whereHas('product', function ($q) use ($areaId) {
                $q->whereHas('store', fn ($s) => $s->where('area_id', $areaId));
            })
            ->orderByDesc('id')
            ->paginate($perPage);

        $paginator->getCollection()->transform(function ($discount) {
            if ($discount->product) {
                $discount->product->quantity = $discount->product->quantity !== null
                    ? (int) $discount->product->quantity
                    : null;
            }
            return $discount;
        });

        return $paginator;
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

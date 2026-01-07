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
        $now = Carbon::now(config('app.timezone'));

        $query = Discount::query()
            ->with([
                'product:id,name,image,store_id,details,price,status',
                'product.store:id,name,area_id,image,open_hour,close_hour,status',
            ])
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->whereHas('product', function ($q) use ($areaId) {
                $q->whereHas('store', fn($s) => $s->where('area_id', $areaId));
            })
            ->orderByDesc('id');

        $paginator = $query->paginate($perPage);

        if ($paginator->total() === 0) {
            Log::info('OfferDiscountRepository.getActiveDiscountsByArea returned 0', [
                'area_id' => $areaId,
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);
        }

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

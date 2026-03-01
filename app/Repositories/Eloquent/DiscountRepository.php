<?php

namespace App\Repositories\Eloquent;

use App\Models\Discount;
use Carbon\Carbon;

class DiscountRepository
{
    public function hasOverlapping(int $productId, Carbon $start, Carbon $end): bool
    {
        return Discount::where('product_id', $productId)
            ->whereNull('deleted_at')
            ->where(function($q) use ($start, $end) {
                $q->whereDate('start_date', '<=', $end)
                    ->whereDate('end_date', '>=', $start);
            })
            ->exists();
    }

    public function create(int $productId, float $newPrice, Carbon $start, Carbon $end): Discount
    {
        return Discount::create([
            'product_id' => $productId,
            'new_price'  => $newPrice,
            'start_date' => $start,
            'end_date'   => $end,
        ]);
    }


    public function createByAdmin(array $data): Discount
    {
        return Discount::create($data);
    }

    public function update(int $id, array $data): ?Discount
    {
        $discount = Discount::find($id);
        if ($discount) {
            $discount->update($data);
        }
        return $discount;
    }

    public function delete(int $id): bool
    {
        $discount = Discount::find($id);
        if (!$discount) return false;

        return $discount->delete();
    }

    public function findById(int $id): ?Discount
    {
        /** @var Discount|null $discount */
        $discount = Discount::with('product')->find($id);
        return $discount;
    }

    public function listByProduct(int $productId)
    {
        return Discount::where('product_id', $productId)
            ->orderByDesc('start_date')
            ->paginate(15);
    }

    public function getDiscountsByAdminArea(int $areaId)
    {
        return Discount::query()
            ->whereHas('product', function ($q) use ($areaId) {
                $q->whereIn('store_id', function ($q2) use ($areaId) {
                    $q2->select('id')
                        ->from('users')
                        ->where('area_id', $areaId);
                });
            })
            ->with(['product:id,name,store_id'])
            ->latest()
            ->get();
    }

    public function getDiscountsByStore(int $storeId)
    {
        return Discount::query()
            ->whereHas('product', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->with(['product:id,name,store_id'])
            ->latest()
            ->get();
    }
}

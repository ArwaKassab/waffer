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
            ->whereIn('status', ['active','scheduled'])
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
}

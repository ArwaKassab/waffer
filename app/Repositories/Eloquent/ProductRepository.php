<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;


class ProductRepository
{
    public function getProductsByIds($ids)
    {
        return Product::whereIn('id', $ids)->get()->keyBy('id');
    }

    public function getActiveDiscount($product, $quantity)
    {
        $activeDiscount = $product->activeDiscountToday();

        if ($activeDiscount) {
            $discountValue = ($product->price - $activeDiscount->new_price) * $quantity;

            return [
                'discount_value' => $discountValue,
                'order_discount_data' => [
                    'product_id' => $product->id,
                    'discount_id' => $activeDiscount->id,
                    'value' => $discountValue,
                ],
            ];
        }

        return null;
    }
}


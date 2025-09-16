<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class ProductRepository
{
    public function getProductById(int $id): ?Product
    {
        /** @var Product|null $product */
        $product = Product::with('store')->find($id);
        return $product;
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
    public function getStoreProducts(int $storeId, int $perPage = 10): LengthAwarePaginator
    {
        // استرجاع المنتجات مع الخصومات إذا كانت موجودة
        $products = Product::query()
            ->from('products')
            ->leftJoin('discounts as d', function ($join) {
                $join->on('d.product_id', '=', 'products.id')
                    ->where('d.status', 'active')
                    ->whereNull('d.deleted_at')
                    ->whereDate('d.start_date', '<=', now())
                    ->whereDate('d.end_date', '>=', now());
            })
            ->where('products.store_id', $storeId)
            ->select(
                'products.id',
                'products.name',
                'products.image',
                'products.status',
                'products.quantity',
                'products.unit',
                'products.price',
            )
            ->selectRaw('COALESCE(d.new_price, products.price) AS new_price')
            ->selectRaw('CASE WHEN d.id IS NULL THEN 0 ELSE 1 END AS has_active_discount')
            ->orderByDesc('has_active_discount')
            ->orderByRaw("CASE WHEN products.status = 'available' THEN 0 WHEN products.status = 'not_available' THEN 1 ELSE 2 END")
            ->orderByDesc('products.created_at')
            ->paginate($perPage);


        $products->getCollection()->transform(function ($product) {
            $product->image = Storage::url($product->image);
            return $product;
        });

        return $products;
    }


}


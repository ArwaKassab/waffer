<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use  App\Repositories\Contracts\StoreRepositoryInterface;
use Carbon\Carbon;


class StoreRepository implements StoreRepositoryInterface
{
    public function getStoresByAreaAndCategory($areaId, $categoryId)
    {
        return User::where('type', 'store')
            ->where('area_id', $areaId)
            ->whereHas('categories', function ($query) use ($categoryId) {
                $query->where('categories.id', $categoryId);
            })
            ->get(['id', 'area_id', 'name', 'image', 'status', 'note', 'open_hour', 'close_hour']);

    }

    public function getStoreDetailsWithProductsAndDiscounts($storeId)
    {
        $store = User::where('type', 'store')
            ->where('id', $storeId)
            ->with('products')  // فقط جلب المنتجات بدون تحميل الخصومات هنا
            ->first(['id', 'name', 'image', 'status', 'note', 'open_hour', 'close_hour']);

        if (!$store) {
            return null;
        }

        // ترتيب المنتجات بحيث التي تحتوي على خصم نشط تأتي أولاً
        $productsWithDiscountsFirst = $store->products->sortByDesc(function ($product) {
            return $product->activeDiscountToday() ? 1 : 0;
        })->values();

        return [
            'store' => $store->only(['id', 'name', 'image', 'status', 'note', 'open_hour', 'close_hour']),
            'products' => $productsWithDiscountsFirst->map(function ($product) {
                $discount = $product->activeDiscountToday();

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image' => $product->image,
                    'status' => $product->status,
                    'unit' => $product->unit,
                    'original_price' => $product->price,
                    'new_price' => $discount?->new_price,
                    'discount_title' => $discount?->title,
                ];
            })
        ];
    }



}

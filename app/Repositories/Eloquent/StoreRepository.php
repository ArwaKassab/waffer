<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use  App\Repositories\Contracts\StoreRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;


class StoreRepository implements StoreRepositoryInterface
{

    public function getStoresByArea(int $areaId)
    {
        $stores = User::where('type', 'store')
            ->where('area_id', $areaId)
            ->get(['id','area_id','name','image','status','note','open_hour','close_hour']);

        $stores->transform(function ($store) {
            $store->image = $store->image ? Storage::url($store->image) : null;
            return $store;
        });

        return $stores;
    }

    public function getStoresByAreaAndCategory($areaId, $categoryId)
    {
        $stores = User::where('type', 'store')
            ->where('area_id', $areaId)
            ->whereHas('categories', function ($query) use ($categoryId) {
                $query->where('categories.id', $categoryId);
            })
            ->get(['id', 'area_id', 'name', 'image', 'status', 'note', 'open_hour', 'close_hour' ]);

        $stores->transform(function ($store) {
            $store->image = $store->image ? Storage::url($store->image) : null;
            return $store;
        });

        return $stores;
    }



    public function getStoreDetailsWithProductsAndDiscounts($storeId)
    {
        $store = User::where('type', 'store')
            ->where('id', $storeId)
            ->with('products','categories')
            ->first(['id', 'name', 'image', 'status', 'note', 'open_hour', 'close_hour']);

        if (!$store) {
            return null;
        }

        $store->image = $store->image ? Storage::url($store->image) : null;

        $productsWithDiscountsFirst = $store->products->sortByDesc(function ($product) {
            return $product->activeDiscountToday() ? 1 : 0;
        })->values();

        return [
            'store' => $store->only(['id', 'name', 'image', 'status', 'note', 'open_hour', 'close_hour']),
            'categories' => $store->categories->map(function ($category) {
                return [
                    'id' => $category->id,
                ];
            }),
            'products' => $productsWithDiscountsFirst->map(function ($product) {
                $discount = $product->activeDiscountToday();

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image' => Storage::url($product->image),
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

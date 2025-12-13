<?php

namespace App\Services;

use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Eloquent\ProductRepository;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    protected $productRepo;

    public function __construct(ProductRepository $orderRepo)
    {
        $this->productRepo = $orderRepo;
    }

    public function getProductDetails(int $id): ?array
    {
        $product = $this->productRepo->getProductById($id);
        if (!$product) {
            return null;
        }

        $data = [
            'id'             => $product->id,
            'name'           => $product->name,
            'image'          => $product->image_url,
            'quantity'       => (int) round((float) $product->quantity),
            'unit'           => $product->unit,
            'details'        => $product->details,
            'isAvailable'    => (bool) $product->status,
            'original_price' => (int) round((float) $product->price),
            'store_id'       => $product->store_id,
            'store_name'     => $product->store?->name,
        ];

        $discount = $product->activeDiscountToday();
        if ($discount) {
            $data['new_price']   = (int) round((float) $discount->new_price);
            $data['discount_id'] = $discount->id;
        }

        return $data;
    }


    /**
     * عرض منتجات المتجر مرتبة:
     * (خصم نشِط أولاً) ثم (available) ثم (not_available) — مع باجينيشن.
     */
    public function listStoreProductsSorted(int $storeId, int $perPage = 10)
    {
        return $this->productRepo->getStoreProducts($storeId, $perPage);
    }

    /**
     * الحصول على الوحدات المسموحة للمنتجات
     */
    public function listUnits(): array
    {
        return $this->productRepo->getUnits();
    }

}


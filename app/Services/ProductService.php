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
            'quantity'       =>$product->quantity,
            'unit'           => $product->unit,
            'status'         => $product->status,
            'original_price' => $product->price,
            'store_id'       => $product->store_id,
            'store_name'     => $product->store?->name,
        ];

        $discount = $product->activeDiscountToday();

        if ($discount) {
            $data['new_price']      = $discount->new_price;
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

}


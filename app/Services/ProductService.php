<?php

namespace App\Services;

use App\Repositories\Contracts\ProductRepositoryInterface;

class ProductService
{
    protected ProductRepositoryInterface $productRepository;

    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function getProductDetails($id)
    {
        $product = $this->productRepository->getProductById($id);

        if (!$product) {
            return null;
        }

        $data = [
            'id' => $product->id,
            'name' => $product->name,
            'image' => $product->image,
            'unit' => $product->unit,
            'status' => $product->status,
            'original_price' => $product->price,
            'store_id' => $product->store_id,
            'store_name' => $product->store ? $product->store->name : null,
        ];

        $discount = $product->activeDiscountToday();

        if ($discount) {
            $data['new_price'] = $discount->new_price;
            $data['old_price'] = $product->price;
            $data['discount_title'] = $discount->title;
        }

        return $data;
    }


}

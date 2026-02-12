<?php

namespace App\Services\SubAdmin;

use App\Repositories\Eloquent\ProductRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ProductService
{
    protected ProductRepository $repository;

    public function __construct(ProductRepository $repository)
    {
        $this->productRepo = $repository;
    }

    public function createProduct(array $data): array
    {

        $product = $this->productRepo->create($data);

        return [
            'success' => true,
            'message' => 'تم إنشاء المنتج بنجاح',
            'product' => $product
        ];
    }

    public function updateProduct(int $productId, array $data, int $area_id): array
    {
        $product = $this->repository
            ->findInAdminArea($productId, $area_id);

        if (! $product) {
            return ['success' => false, 'message' => 'المنتج غير موجود أو لا ينتمي لمنطقتك'];
        }

        $this->repository->update($product, $data);

        return [
            'success' => true,
            'message' => 'تم تعديل المنتج بنجاح',
            'product' => $product->fresh()
        ];
    }

    public function deleteProduct(int $productId, int $area_id): array
    {
        $product = $this->repository
            ->findInAdminArea($productId, $area_id);

        if (! $product) {
            return ['success' => false, 'message' => 'المنتج غير موجود أو لا ينتمي لمنطقتك'];
        }

        $this->repository->delete($product);

        return [
            'success' => true,
            'message' => 'تم حذف المنتج بنجاح'
        ];
    }

}

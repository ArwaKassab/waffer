<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Services\SubAdmin\ProductRequestService;
use App\Services\SubAdmin\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    public function store(ProductRequest  $request)
    {
        $data = $request->validated();
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }
        $product = $this->productService->createProduct($data, auth()->user());

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء المنتج بنجاح',
            'product' => $product,
        ]);
    }

    public function update(ProductRequest $request, int $id)
    {
        $data = $request->validated();

        return response()->json(
            $this->productService->updateProduct(
                $id,
                $data,
                $request->area_id
            )
        );
    }

    public function destroy(int $id ,Request $request)
    {
        return response()->json(
            $this->productService->deleteProduct(
                $id,
                $request->area_id
            )
        );
    }
}

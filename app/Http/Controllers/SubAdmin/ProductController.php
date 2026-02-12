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

    public function store(ProductRequest $request)
    {
        $data = $request->validated();
        return response()->json(
            $this->productService->createProduct($data, auth()->user())
        );
    }
    public function update(Request $request, int $id)
    {
        return response()->json(
            $this->productService->updateProduct(
                $id,
                $request->validated(),
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

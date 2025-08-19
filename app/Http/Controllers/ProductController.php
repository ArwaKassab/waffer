<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProductService;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function productDetails($id)
    {
        $product = $this->productService->getProductDetails($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

     public function myStoreProducts(Request $request)
     {
         $storeId = auth()->id();
         $perPage = (int) $request->query('per_page', 10);
         return $this->productService->listStoreProductsSorted($storeId, $perPage);
     }
}

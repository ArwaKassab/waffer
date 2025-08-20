<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
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

    public function searchProductsInStore( User $store,Request $request)
    {


        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q, 'UTF-8') < 2) {
            return response()->json([
                'message'   => 'أدخل حرفين على الأقل للبحث.',
                'store_id'  => $store->id,
                'store_name'=> $store->name,
                'products'  => [],
            ], 200);
        }

        $tokens = collect(preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($t) => trim($t))
            ->filter(fn ($t) => mb_strlen($t, 'UTF-8') >= 2)
            ->values();

        $escapeRegex = fn (string $t): string => preg_quote($t, '/');

        $products = Product::query()
            ->where('store_id', $store->id)
            ->select('id', 'name', 'price', 'store_id')
            ->when($tokens->isNotEmpty(), function ($query) use ($tokens, $escapeRegex) {
                foreach ($tokens as $t) {
                    $re = $escapeRegex($t);
                    $pattern1 = '^[[:space:]]*' . $re;
                    $pattern2 = '(^|[[:space:][:punct:]])(ال)?' . $re;

                    $query->where(function ($qq) use ($pattern1, $pattern2) {
                        $qq->whereRaw("`name` REGEXP ?", [$pattern1])
                            ->orWhereRaw("`name` REGEXP ?", [$pattern2]);
                    });
                }
            })
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json([
            'q'          => $q,
            'store_id'   => $store->id,
            'store_name' => $store->name,
            'products'   => $products,
        ]);
    }
}


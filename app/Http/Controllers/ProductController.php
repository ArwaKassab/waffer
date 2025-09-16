<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductDiscountRequest;
use App\Models\Product;
use App\Models\User;
use App\Services\DiscountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\ProductService;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    protected ProductService $productService;
    private DiscountService $discountService;

    public function __construct(ProductService $productService , DiscountService $discountService)
    {
        $this->productService = $productService;
        $this->discountService = $discountService;

    }
    public function productDetails($id)
    {
        Log::info("Fetching product details for ID: {$id}");  // السجل هنا
        $product = $this->productService->getProductDetails($id);

        if (!$product) {
            Log::info("Product with ID {$id} not found");  // السجل هنا
            return response()->json(['message' => 'Product not found'], 404);
        }

        Log::info("Product details fetched successfully for ID: {$id}");  // السجل هنا
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

    public function addDiscount(StoreProductDiscountRequest $request, int $productId): JsonResponse
    {
        $storeId = (int) auth()->id();

        [$product, $discount] = $this->discountService->addByStore(
            $storeId,
            $productId,
            $request->validated()
        );

        return response()->json([
            'message'      => 'تم إضافة الخصم بنجاح.',
            'product_id'   => $product->id,
            'original_price' => $product->price,
            'discount'     => [
                'id'         => $discount->id,
                'new_price'  => $discount->new_price,
                'start_date' => $discount->start_date->toDateTimeString(),
                'end_date'   => $discount->end_date->toDateTimeString(),
            ],
        ], 201);
    }

}


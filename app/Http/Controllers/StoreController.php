<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\StoreService;

class StoreController extends Controller
{
    protected $storeService;

    public function __construct(StoreService $storeService)
    {
        $this->storeService = $storeService;
    }
    public function indexByArea(Request $request)
    {
        $areaId = $request->get('area_id');

        if (!$areaId) {
            return response()->json(['message' => 'Area not set'], 400);
        }

        $stores = $this->storeService->getStoresByArea($areaId);

        return response()->json($stores);
    }

    public function index(Request $request,$categoryId)
    {
        $areaId = $request->get('area_id');

        if (!$areaId) {
            return response()->json(['message' => 'Area not set'], 400);
        }

        if (!$categoryId) {
            return response()->json(['message' => 'Category not set'], 400);
        }

        $stores = $this->storeService->getStores($areaId, $categoryId);

        return response()->json($stores);
    }

    public function show($id)
    {
        $storeDetails = $this->storeService->getStoreDetails($id);

        if (!$storeDetails) {
            return response()->json(['message' => 'Store not found'], 404);
        }

        return response()->json($storeDetails);
    }


    public function search(Request $request)
    {


        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q, 'UTF-8') < 2) {
            return response()->json([
                'message' => 'أدخل حرفين على الأقل للبحث.',
                'stores'  => [],
                'products'=> [],
            ], 200);
        }

        // جزّئي لعبارات (AND) بطول ≥ 2
        $tokens = collect(preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($t) => trim($t))
            ->filter(fn ($t) => mb_strlen($t, 'UTF-8') >= 2)
            ->values();

        $escapeRegex = fn (string $t): string => preg_quote($t, '/');

        // ===== المتاجر =====
        $stores = \App\Models\User::query()
            ->where('type', 'store')
            ->where('area_id', $request->area_id)
            ->select('id', 'name')
            ->when($tokens->isNotEmpty(), function ($query) use ($tokens, $escapeRegex) {
                foreach ($tokens as $t) {
                    $re = $escapeRegex($t);
                    // بداية النص أو بداية كلمة (مع "ال" اختياري)
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

        // ===== المنتجات =====
        $products = Product::query()
            ->with(['store:id,name,area_id'])
            ->whereHas('store', function ($q) use ($request) {
                $q->where('type', 'store')->where('area_id', $request->area_id);
            })
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
            'q'        => $q,
            'area_id'  => (int) $request->area_id,
            'stores'   => $stores,
            'products' => $products,
        ]);
    }


//
//    public function searchByCategory(Request $request, int $categoryId)
//    {
//
//
//        $q = trim((string) $request->query('q', ''));
//        if (mb_strlen($q, 'UTF-8') < 2) {
//            return response()->json([
//                'message'  => 'أدخل حرفين على الأقل للبحث.',
//                'stores'   => [],
//                'products' => [],
//            ], 200);
//        }
//
//        // تقسيم كلمات البحث (AND) بطول ≥ 2
//        $tokens = collect(preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY))
//            ->map(fn ($t) => trim($t))
//            ->filter(fn ($t) => mb_strlen($t, 'UTF-8') >= 2)
//            ->values();
//
//        $escape = fn (string $t): string => preg_quote($t, '/');
//        $buildPatterns = function (string $term) use ($escape) {
//            $re = $escape($term);
//            // بداية النص أو بداية كلمة مع "ال" اختياري
//            return [
//                '^[[:space:]]*' . $re,
//                '(^|[[:space:][:punct:]])(ال)?' . $re,
//            ];
//        };
//
//        // ===== المتاجر ضمن التصنيف + نفس منطقة الزبون =====
//        $stores = User::query()
//            ->where('type', 'store')
//            ->where('area_id', $request->area_id)
//            ->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId))
//            ->select('id', 'name')
//            ->when($tokens->isNotEmpty(), function ($query) use ($tokens, $buildPatterns) {
//                foreach ($tokens as $t) {
//                    [$p1, $p2] = $buildPatterns($t);
//                    $query->where(function ($qq) use ($p1, $p2) {
//                        $qq->whereRaw("`name` REGEXP ?", [$p1])
//                            ->orWhereRaw("`name` REGEXP ?", [$p2]);
//                    });
//                }
//            })
//            ->orderBy('name')
//            ->limit(10)
//            ->get();
//
//        // ===== المنتجات عبر تصنيف متجرها + نفس منطقة الزبون =====
//        $products = Product::query()
//            ->with(['store:id,name,area_id']) // تأكد علاقة store() موجودة في Product
//            ->whereHas('store', function ($qs) use ($request, $categoryId) {
//                $qs->where('type', 'store')
//                    ->where('area_id', $request->area_id)
//                    ->whereHas('categories', fn ($qc) => $qc->where('categories.id', $categoryId));
//            })
//            ->select('id', 'name', 'price', 'store_id')
//            ->when($tokens->isNotEmpty(), function ($query) use ($tokens, $buildPatterns) {
//                foreach ($tokens as $t) {
//                    [$p1, $p2] = $buildPatterns($t);
//                    $query->where(function ($qq) use ($p1, $p2) {
//                        $qq->whereRaw("`name` REGEXP ?", [$p1])
//                            ->orWhereRaw("`name` REGEXP ?", [$p2]);
//                    });
//                }
//            })
//            ->orderBy('name')
//            ->limit(10)
//            ->get();
//
//        return response()->json([
//            'q'           => $q,
//            'area_id'     => (int) $request->area_id,
//            'category_id' => (int) $categoryId,
//            'stores'      => $stores,
//            'products'    => $products,
//        ]);
//    }

    public function searchByCategoryGrouped(Request $request, int $categoryId)
    {
        $q = trim((string) $request->query('q', ''));
        $areaId = (int) $request->query('area_id');

        if (!$areaId) {
            return response()->json([
                'q' => $q, 'area_id' => $areaId, 'category_id' => $categoryId,
                'stores' => [], 'message' => 'Area not set',
            ], 400);
        }

        if (mb_strlen($q, 'UTF-8') < 2) {
            return response()->json([
                'q' => $q, 'area_id' => $areaId, 'category_id' => $categoryId,
                'stores' => [], 'message' => 'أدخل حرفين على الأقل للبحث.',
            ], 200);
        }

        // productsPerStoreLimit = null لو بدك كل منتجات المتجر
        $stores = $this->storeService->searchStoresAndProductsGrouped(
            areaId: $areaId,
            categoryId: $categoryId,
            q: $q,
            productsPerStoreLimit: 10
        );

        return response()->json([
            'q' => $q,
            'area_id' => $areaId,
            'category_id' => $categoryId,
            'stores' => $stores, // مصفوفة متاجر موحّدة
        ], 200);
    }

    public function searchGroupedInArea(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $areaId = (int) $request->query('area_id');

        if (!$areaId) {
            return response()->json([
                'q' => $q, 'area_id' => $areaId,
                'stores' => [], 'message' => 'Area not set',
            ], 400);
        }

        if (mb_strlen($q, 'UTF-8') < 2) {
            return response()->json([
                'q' => $q, 'area_id' => $areaId,
                'stores' => [], 'message' => 'أدخل حرفين على الأقل للبحث.',
            ], 200);
        }

        // productsPerStoreLimit = null لو بدك كل منتجات المتجر عند تطابق اسم المتجر
        $stores = $this->storeService->searchStoresAndProductsGroupedInArea(
            areaId: $areaId,
            q: $q,
            productsPerStoreLimit: 10
        );

        return response()->json([
            'q' => $q,
            'area_id' => $areaId,
            'stores' => $stores,
        ], 200);
    }


}

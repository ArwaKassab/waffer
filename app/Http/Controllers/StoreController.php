<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
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
        $areaId  = (int) $request->query('area_id');
        $perPage = (int) $request->query('per_page', 15);
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;

        if (!$areaId) {
            return response()->json(['message' => 'Area not set'], 400);
        }

        $stores = $this->storeService->getStoresByArea($areaId, $perPage);


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

    public function searchUnified(Request $request)
    {
        $q       = trim((string) $request->query('q', ''));
        $areaId  = (int) ($request->query('area_id'));
        $limit   = (int) $request->query('limit', 10);      // عدد المنتجات كحد أقصى لكل متجر عند البحث
        $perPage = (int) $request->query('per_page', 20);   // التصفح عند عدم وجود بحث
        $hasSearch = ($q !== '') && (mb_strlen($q, 'UTF-8') >= 2);

        if (!$areaId) {
            return response()->json([
                'q' => $q, 'area_id' => $areaId, 'category_id' => null,
                'stores' => [], 'message' => 'Area not set',
            ], 400);
        }

        // 🔎 استلام التصنيف كـ query param:
        // - لو فيه category_id ناخده كما هو (int)
        // - لو فيه category (اسم) نحوله لـ id
        $categoryId = $request->integer('category_id') ?: null;
        if (!$categoryId && $request->filled('category')) {
            $categoryName = trim((string) $request->query('category'));
            $categoryId = Category::where('name', $categoryName)->value('id'); // طبقّيها حسب سكيمتك
            // ملاحظة: فيكِ تعملي whereRaw('LOWER(name)=LOWER(?)', [$categoryName]) لو بدك case-insensitive
        }

        // ✅ السيناريو 1: لا بحث + لا تصنيف => كل متاجر المنطقة (paginated)
        if (!$hasSearch && !$categoryId) {
            $paginator = $this->storeService->getStoresByArea($areaId, $perPage)
                ->through(fn ($s) => [
                    'id'         => $s->id,
                    'name'       => $s->name,
                    'area_id'    => $s->area_id,
                    'status'     => $s->status,
                    'note'       => $s->note,
                    'open_hour'  => $s->open_hour,
                    'close_hour' => $s->close_hour,
                    'image'      => $s->image_url,   // رابط كامل
                    'image_url'  => $s->image_url,   // (اختياري)
                ]);

            return response()->json([
                'mode'        => 'browse_all',
                'q'           => $q,
                'area_id'     => $areaId,
                'category_id' => null,
                'stores'      => $paginator,
            ], 200);
        }

        // ✅ السيناريو 2: لا بحث + مع تصنيف => فلترة بالتصنيف فقط (paginated)
        if (!$hasSearch && $categoryId) {
            $paginator = $this->storeService
                ->getStoresByAreaAndCategoryPaged($areaId, $categoryId, $perPage)
                ->through(fn ($s) => [
                    'id'         => $s->id,
                    'name'       => $s->name,
                    'area_id'    => $s->area_id,
                    'status'     => $s->status,
                    'note'       => $s->note,
                    'open_hour'  => $s->open_hour,
                    'close_hour' => $s->close_hour,
                    'image'      => $s->image_url,
                    'image_url'  => $s->image_url,
                ]);

            return response()->json([
                'mode'        => 'filter_only',
                'q'           => $q,
                'area_id'     => $areaId,
                'category_id' => $categoryId,
                'stores'      => $paginator,
            ], 200);
        }

        // 🚦 تنبيه البحث: لو q موجودة لكنها أقل من حرفين
        if ($q !== '' && !$hasSearch) {
            return response()->json([
                'mode'        => $categoryId ? 'filter_and_search' : 'search_only',
                'q'           => $q,
                'area_id'     => $areaId,
                'category_id' => $categoryId,
                'stores'      => [],
                'message'     => 'أدخل حرفين على الأقل للبحث.',
            ], 200);
        }

        // ✅ السيناريو 3 و 4: بحث فقط أو بحث + تصنيف
        $stores = $this->storeService->searchStoresAndProductsGroupedUniversal(
            areaId: $areaId,
            q: $q,
            productsPerStoreLimit: $limit,
            categoryId: $categoryId
        );

        return response()->json([
            'mode'        => $categoryId ? 'filter_and_search' : 'search_only',
            'q'           => $q,
            'area_id'     => $areaId,
            'category_id' => $categoryId,
            'stores'      => $stores,
        ], 200);
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




}

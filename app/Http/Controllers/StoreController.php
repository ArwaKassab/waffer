<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Services\StoreService;

class StoreController extends Controller
{
    protected StoreService $storeService;

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

        // يرجّع paginator و items داخله Arrays (حسب كودك الجديد بالريبو)
        $stores = $this->storeService->getStoresByArea($areaId, $perPage);

        return response()->json($stores);
    }

    public function index(Request $request, $categoryId)
    {
        $areaId = (int) $request->get('area_id');

        if (!$areaId) {
            return response()->json(['message' => 'Area not set'], 400);
        }

        if (!$categoryId) {
            return response()->json(['message' => 'Category not set'], 400);
        }

        // مفترض يرجع Collection من Arrays
        $stores = $this->storeService->getStores($areaId, (int) $categoryId);

        return response()->json($stores);
    }

    public function show($id)
    {
        // مفترض يرجع Array جاهزة
        $storeDetails = $this->storeService->getStoreDetails((int) $id);

        if (!$storeDetails) {
            return response()->json(['message' => 'Store not found'], 404);
        }

        return response()->json($storeDetails);
    }

    public function searchGroupedInArea(Request $request)
    {
        $q      = trim((string) $request->query('q', ''));
        $areaId = (int) $request->query('area_id');

        if (!$areaId) {
            return response()->json([
                'q' => $q,
                'area_id' => $areaId,
                'stores' => [],
                'message' => 'Area not set',
            ], 400);
        }

        if (mb_strlen($q, 'UTF-8') < 2) {
            return response()->json([
                'q' => $q,
                'area_id' => $areaId,
                'stores' => [],
                'message' => 'أدخل حرفين على الأقل للبحث.',
            ], 200);
        }

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
        $q         = trim((string) $request->query('q', ''));
        $areaId    = (int) $request->query('area_id');
        $limit     = (int) $request->query('limit', 10);
        $perPage   = (int) $request->query('per_page', 20);

        $limit   = $limit > 0 ? min($limit, 50) : 10;
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;

        $hasSearch = ($q !== '') && (mb_strlen($q, 'UTF-8') >= 2);

        if (!$areaId) {
            return response()->json([
                'q' => $q,
                'area_id' => $areaId,
                'category_ids' => [],
                'stores' => [],
                'message' => 'Area not set',
            ], 400);
        }

        // اجمع category_ids كـ مصفوفة أرقام
        $categoryIds = collect((array) $request->query('category_ids', []))
            ->flatMap(function ($v) {
                if (is_string($v) && str_contains($v, ',')) {
                    return array_map('trim', explode(',', $v));
                }
                return [$v];
            })
            ->filter(fn ($v) => $v !== '' && $v !== null)
            ->map(fn ($v) => (int) $v)
            ->filter()
            ->unique()
            ->values()
            ->all();

        // لو ما وصل IDs، اسمح بأسماء: categories=خضار,مواد غذائية
        if (empty($categoryIds) && $request->filled('categories')) {
            $names = collect(explode(',', (string) $request->query('categories')))
                ->map(fn ($s) => trim($s))
                ->filter()
                ->unique()
                ->values();

            if ($names->isNotEmpty()) {
                $categoryIds = Category::whereIn('name', $names)->pluck('id')->all();
            }
        }

        $matchMode = strtolower((string) $request->query('match', 'all')); // AND افتراضي
        if (!in_array($matchMode, ['any', 'all'], true)) {
            $matchMode = 'all';
        }

        // 1) لا بحث + لا تصنيفات => كل المتاجر (paginated)
        if (!$hasSearch && empty($categoryIds)) {
            $paginator = $this->storeService->getStoresByArea($areaId, $perPage);

            // ✅ مهم: لا تعمل transform هنا، لأن العناصر أصلاً Arrays جاهزة من الريبو
            return response()->json([
                'mode'         => 'browse_all',
                'q'            => $q,
                'area_id'      => $areaId,
                'category_ids' => [],
                'stores'       => $paginator,
            ], 200);
        }

        // 2) لا بحث + مع تصنيفات (متعددة) => فلترة فقط (paginated)
        if (!$hasSearch && !empty($categoryIds)) {
            $paginator = $this->storeService->getStoresByAreaAndCategoriesPaged(
                $areaId,
                $categoryIds,
                $perPage,
                $matchMode
            );

            // ✅ نفس الشي: لا transform
            return response()->json([
                'mode'         => 'filter_only',
                'q'            => $q,
                'area_id'      => $areaId,
                'category_ids' => $categoryIds,
                'match'        => $matchMode,
                'stores'       => $paginator,
            ], 200);
        }

        // تنبيه: q أقل من حرفين
        if ($q !== '' && !$hasSearch) {
            return response()->json([
                'mode'         => !empty($categoryIds) ? 'filter_and_search' : 'search_only',
                'q'            => $q,
                'area_id'      => $areaId,
                'category_ids' => $categoryIds,
                'match'        => $matchMode,
                'stores'       => [],
                'message'      => 'أدخل حرفين على الأقل للبحث.',
            ], 200);
        }

        // 3/4) بحث فقط أو بحث + تصنيفات متعددة (array)
        $stores = $this->storeService->searchStoresAndProductsGroupedUniversalMulti(
            areaId: $areaId,
            q: $q,
            productsPerStoreLimit: $limit,
            categoryIds: $categoryIds ?: null,
            matchMode: $matchMode
        );

        return response()->json([
            'mode'         => !empty($categoryIds) ? 'filter_and_search' : 'search_only',
            'q'            => $q,
            'area_id'      => $areaId,
            'category_ids' => $categoryIds,
            'match'        => $matchMode,
            'stores'       => $stores,
        ], 200);
    }

    public function searchByCategoryGrouped(Request $request, int $categoryId)
    {
        $q      = trim((string) $request->query('q', ''));
        $areaId = (int) $request->query('area_id');

        if (!$areaId) {
            return response()->json([
                'q' => $q,
                'area_id' => $areaId,
                'category_id' => $categoryId,
                'stores' => [],
                'message' => 'Area not set',
            ], 400);
        }

        if (mb_strlen($q, 'UTF-8') < 2) {
            return response()->json([
                'q' => $q,
                'area_id' => $areaId,
                'category_id' => $categoryId,
                'stores' => [],
                'message' => 'أدخل حرفين على الأقل للبحث.',
            ], 200);
        }

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
            'stores' => $stores,
        ], 200);
    }
}

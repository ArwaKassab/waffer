<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\StoreAdminShowResource;
use App\Http\Resources\StoreResource;
use App\Models\User;
use App\Services\SubAdmin\StoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    public function __construct(
        protected StoreService $storeService
    ) {
    }

    /**
     * عرض المتاجر في نفس منطقة الأدمن الفرعي.
     */
    public function allArea(Request $request)
    {
        $perPage = (int) $request->get('per_page', 20);

        $storesPaginator = $this->storeService->getStoresForCurrentAdminArea($request,$perPage);

        if ($storesPaginator === null) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك أو لا يوجد منطقة مخصصة لك.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'current_page' => $storesPaginator->currentPage(),
                'data'         => $storesPaginator->items(), // العناصر فقط
            ],
        ]);
    }

    /**
     * إضافة متجر جديد.
     */
    public function addStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'user_name'   => ['required', 'string', 'max:255', Rule::unique('users', 'user_name')],
            'phone' => [
                'required',
                'regex:/^09\d{8}$/',
                Rule::unique('users', 'phone')->where(fn ($q) => $q->where('type', 'store')),
            ],

            'area_id'     => ['required', 'exists:areas,id'],
            'password'    => ['required', 'string', 'min:6'],
            'status'      => ['required', 'boolean'],
            'open_hour'   => ['required', 'date_format:H:i'],
            'close_hour'  => ['required', 'date_format:H:i'],
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'note'        => ['nullable', 'string'],
            'category_ids'   => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ]);


        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('stores', 'public');
            $validated['image'] = $path;
        }

        $store = $this->storeService->createStore($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء المتجر بنجاح.',
            'data'    => new StoreResource($store),
        ], 201);
    }



    /**
     * تعديل متجر موجود.
     */
    public function update(Request $request, int $storeId): JsonResponse
    {
        /** @var \App\Models\User $store */
        $store = User::where('id', $storeId)
            ->where('type', 'store')
            ->firstOrFail();

        $validated = $request->validate([
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
            'user_name'   => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'user_name')->ignore($store->id),
            ],
            'phone' => [
                'sometimes',
                'required',
                'regex:/^09\d{8}$/',
                Rule::unique('users', 'phone')
                    ->where(fn ($q) => $q->where('type', 'store'))
                    ->ignore($store->id),
            ],

            'password'    => ['sometimes', 'nullable', 'string', 'min:6'],
            'area_id'     => ['sometimes', 'nullable', 'exists:areas,id'],
            'status'      => ['sometimes', 'boolean'],
            'open_hour'   => ['sometimes', 'nullable', 'date_format:H:i'],
            'close_hour'  => ['sometimes', 'nullable', 'date_format:H:i'],
            'note'        => ['sometimes', 'nullable', 'string'],
            'image'       => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'category_ids'   => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ]);
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('stores', 'public');
            $validated['image'] = $path;
        }
        $updated = $this->storeService->updateStore($store, $validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل بيانات المتجر بنجاح.',
            'data'    => new StoreResource($updated),
        ]);
    }

    /**
     * حذف متجر (Soft Delete) تابع لمنطقة معيّنة.
     */
    public function destroy(Request $request, int $storeId)
    {
        $deleted = $this->storeService->deleteStoreForAdmin($request, $storeId);

        if (! $deleted) {
            return response()->json([
                'success' => false,
                'message' => 'المتجر غير موجود أو لا يتبع لهذه المنطقة.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المتجر بنجاح (Soft Delete).',
        ]);
    }

    public function show(Request $request, int $storeId): JsonResponse
    {

        $store = $this->storeService->getStoreDetailsForAdmin(
            storeId: $storeId,
        );

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'المتجر غير موجود.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new StoreAdminShowResource($store),
        ]);
    }

    /**
     * تعديل حالة المنتج (available / not_available) من قبل الأدمن.
     */
    public function updateStatus(Request $request, int $product): JsonResponse
    {
        $user = auth('sanctum')->user();


        $validated = $request->validate([
            'status' => ['required', Rule::in(['available', 'not_available'])],
        ]);

        $updated = $this->storeService->updateProductStatus(
            productId: $product,
            status: $validated['status'],
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل حالة المنتج بنجاح.',
            'data' => [
                'id' => $updated->id,
                'status' => $updated->status,
                'updated_at' => optional($updated->updated_at)->toDateTimeString(),
            ],
        ]);
    }
}


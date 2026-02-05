<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Services\SubAdmin\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $service
    ) {}

//    public function index(Request $request)
//    {
//        if (!$request->area_id) {
//            return response()->json([
//                'message' => 'المنطقة مطلوبة'
//            ], 422);
//        }
//
//        $paged   = filter_var($request->query('paged', true), FILTER_VALIDATE_BOOLEAN);
//        $perPage = (int) $request->query('per_page', 20);
//        $perPage = $perPage > 0 ? min($perPage, 100) : 20;
//
//        $result = $this->service->listAll(
//            (int) $request->area_id,
//            $paged,
//            $perPage
//        );
//
//        $items = $result instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
//            ? collect($result->items())
//            : $result;
//
//        return response()->json([
//            'data' => $items->map(fn ($c) => [
//                'id'    => $c->id,
//                'name'  => $c->name,
//                'image' => $c->image_url,
//            ])->values(),
//        ]);
//    }
//
//    public function assignToArea(Request $request)
//    {
//        $data = $request->validate([
//            'category_id' => ['required', 'exists:categories,id'],
//            'area_id'     => ['required', 'exists:areas,id'],
//        ]);
//
//        $this->service->addCategoryToArea(
//            $data['category_id'],
//            $data['area_id']
//        );
//
//        return response()->json([
//            'message' => 'تمت إضافة التصنيف إلى المنطقة بنجاح',
//        ]);
//    }


//    public function storeCategoryForArea(Request $request)
//    {
//        $request->validate([
//            'name'  => 'required|string|max:255',
//            'image' => 'nullable|string',
//        ]);
//
//        $areaId = $request->area_id;
//
//        DB::transaction(function () use ($request, $areaId) {
//            $category = Category::create([
//                'name'  => $request->name,
//                'image' => $request->image,
//            ]);
//
//            Area::findOrFail($areaId)
//                ->categories()
//                ->attach($category->id);
//        });
//
//        return response()->json([
//            'message' => 'تم إنشاء التصنيف وربطه بالمنطقة'
//        ]);
//    }
//
//    public function unassigned(Request $request)
//    {
//        $areaId = $request->area_id;
//
//        $categories = $this->service->listUnassignedForArea($areaId);
//
//        return response()->json([
//            'data' => $categories->map(fn ($c) => [
//                'id'    => $c->id,
//                'name'  => $c->name,
//                'image' => $c->image_url,
//            ])->values(),
//        ]);
//    }
//
//
//
//    public function store(Request $request)
//    {
//        $data = $request->validate([
//            'name' => [
//                'required', 'string', 'max:255',
//                Rule::unique('categories', 'name')->whereNull('deleted_at'),
//            ],
//            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
//        ], [
//            'name.unique'   => 'اسم التصنيف موجود مسبقًا.',
//            'name.required' => 'اسم التصنيف مطلوب.',
//            'name.max'      => 'اسم التصنيف يجب ألا يتجاوز 255 حرفًا.',
//            'image.image'   => 'الملف المرفق يجب أن يكون صورة.',
//            'image.mimes'   => 'صيغة الصورة غير مدعومة.',
//            'image.max'     => 'حجم الصورة كبير.',
//        ]);
//
//        $areaId = $request->area_id;
//
//        if (!$areaId) {
//            return response()->json([
//                'message' => 'لا يوجد منطقة مرتبطة بهذا الحساب'
//            ], 403);
//        }
//
//        // رفع الصورة
//        if ($request->hasFile('image')) {
//            $data['image'] = $request->file('image')->store('categories', 'public');
//        }
//
//        $category = $this->service->createAndAttachToArea($areaId, $data);
//
//        return response()->json([
//            'message' => 'تم إنشاء التصنيف وربطه بالمنطقة بنجاح.',
//            'data'    => $category,
//        ], 201);
//    }
//

// جميع التصنيفات
    public function index()
    {
        $categories = $this->service->listAll();

        return response()->json([
            'data' => $categories->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'image' => $c->image_url,
            ])->values(),
        ]);
    }

    // تصنيفات منطقة معينة
    public function byArea(Request $request)
    {
        $areaId = $request->area_id;
        $categories = $this->service->listForArea($areaId);

        return response()->json([
            'data' => $categories->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'image' => $c->image_url,
            ])->values(),
        ]);
    }

    // تصنيفات غير مرتبطة بمنطقة
    public function unassigned(Request $request)
    {
        $areaId = $request->area_id;
        $categories = $this->service->listUnassignedForArea($areaId);

        return response()->json([
            'data' => $categories->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'image' => $c->image_url,
            ])->values(),
        ]);
    }

    // ربط تصنيف بمنطقة
    public function assign(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required','exists:categories,id'],
            'area_id' => ['required','exists:areas,id'],
        ]);

        $this->service->assignToArea($data['category_id'], $data['area_id']);

        return response()->json(['message' => 'تمت إضافة التصنيف للمنطقة بنجاح']);
    }

    // فك الربط من منطقة
    public function detach(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required','exists:categories,id'],
            'area_id' => ['required','exists:areas,id'],
        ]);

        $this->service->removeFromArea($data['category_id'], $data['area_id']);

        return response()->json(['message' => 'تمت إزالة التصنيف من المنطقة بنجاح']);
    }


    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'name' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('categories', 'name')->whereNull('deleted_at')->ignore($id),
            ],
            'image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ], [
            'name.unique'   => 'اسم التصنيف موجود مسبقًا.',
            'name.required' => 'اسم التصنيف مطلوب.',
            'name.max'      => 'اسم التصنيف يجب ألا يتجاوز 255 حرفًا.',
            'image.image'   => 'الملف المرفق يجب أن يكون صورة.',
            'image.mimes'   => 'صيغة الصورة غير مدعومة.',
            'image.max'     => 'حجم الصورة كبير.',
        ]);

        // إذا وصل ملف صورة خزّنيه وخلي data['image'] = path
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category = $this->service->update($id, $data);

        if (!$category) {
            return response()->json(['message' => 'التصنيف غير موجود.'], 404);
        }

        return response()->json([
            'message' => 'تم تعديل التصنيف بنجاح.',
            'data'    => $category,
        ]);
    }

    public function destroy(int $id)
    {
        $deleted = $this->service->delete($id);

        if (!$deleted) {
            return response()->json([
                'status'  => false,
                'message' => 'التصنيف غير موجود.',
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'تم حذف التصنيف بنجاح.',
        ]);
    }

    //////super admin

    public function store_super_admin(Request $request)
    {

        $data = $request->validate([
            'name' => ['required','string','max:255',
                Rule::unique('categories')->whereNull('deleted_at')],
            'image' => ['nullable','image','mimes:jpg,jpeg,png,webp,gif','max:5120'],
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories','public');
        }

        $category = $this->service->create_by_super_admin($data);

        return response()->json([
            'message' => 'تم إنشاء التصنيف بنجاح.',
            'data' => $category,
        ], 201);
    }


}

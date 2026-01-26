<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;
use App\Services\SubAdmin\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $service
    ) {}

    public function index(Request $request)
    {
        $paged   = filter_var($request->query('paged', true), FILTER_VALIDATE_BOOLEAN);
        $perPage = (int) $request->query('per_page', 20);
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;

        $result = $this->service->listAll($paged, $perPage);

        $items = $result instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
            ? collect($result->items())
            : $result;

        return response()->json([
            'data'   => $items->map(fn($c) => [
                'id' =>$c->id,
                'name'  => $c->name,
                'image' => $c->image_url,
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => [
                'required','string','max:255',
                Rule::unique('categories', 'name')->whereNull('deleted_at'),
            ],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ], [
            'name.unique'   => 'اسم التصنيف موجود مسبقًا.',
            'name.required' => 'اسم التصنيف مطلوب.',
            'name.max'      => 'اسم التصنيف يجب ألا يتجاوز 255 حرفًا.',
            'image.image'   => 'الملف المرفق يجب أن يكون صورة.',
            'image.mimes'   => 'صيغة الصورة غير مدعومة.',
            'image.max'     => 'حجم الصورة كبير.',
        ]);

        // خزّن الصورة وأرسل المسار للخدمة
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category = $this->service->create($data);

        return response()->json([
            'message' => 'تم إنشاء التصنيف بنجاح.',
            'data'    => $category,
        ], 201);
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
}

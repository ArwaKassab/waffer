<?php

namespace App\Http\Controllers\SubAdmin;
use App\Http\Controllers\Controller;
use App\Services\SubAdmin\AdService;
use App\Services\AreaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AreaAdController extends Controller
{
    public function __construct(protected AdService $service) {}

    // جلب الإعلانات لمنطقة معينة
    public function index(Request $request): JsonResponse
    {
        $request->validate(['area_id' => 'required|exists:areas,id']);
        $ads = $this->service->getAdsByArea((int) $request->area_id);

        return response()->json([
            'data' => $ads->map(fn($ad) => [
                'id' => $ad->id,
                'image' => $ad->image,
            ]),
        ]);
    }

    // إضافة إعلان لمنطقة
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'area_id' => 'required|exists:areas,id',
            'image' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
        ]);

        // حفظ الصورة في التخزين
//        $path = $request->file('image')->store('ads', 'public');

        $ad = $this->service->addAdToArea((int) $request->area_id, $request->file('image'));

        return response()->json([
            'message' => 'تم إضافة الإعلان بنجاح.',
            'data' => [
                'id' => $ad->id,
                'image' => $ad->image,
            ],
        ], 201);
    }

    // حذف إعلان من منطقة
    public function destroy(Request $request, int $adId): JsonResponse
    {
        $request->validate(['area_id' => 'required|exists:areas,id']);

        $this->service->removeAdFromArea((int) $request->area_id, $adId);

        return response()->json(['message' => 'تم حذف الإعلان بنجاح.']);
    }
}

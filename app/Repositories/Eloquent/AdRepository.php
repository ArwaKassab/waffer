<?php

namespace App\Repositories\Eloquent;

use App\Models\Ad;
use App\Models\Area;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Contracts\AdRepositoryInterface;

class AdRepository implements AdRepositoryInterface
{
    public function createWithImage(UploadedFile $image): array
    {
        $path = Storage::disk('public')->put('ads', $image);

        $ad = Ad::create([
            'image' => $path,
        ]);

        return [
            'id' => $ad->id,
            'image_url' => asset('storage/' . $ad->image),
        ];
    }

    public function getLatestAds(int $limit = 4): array
    {
        return Ad::latest()
            ->take($limit)
            ->get()
            ->map(fn($ad) => [
                'id' => $ad->id,
                'image_url' => asset('storage/' . $ad->image),
            ])
            ->toArray();
    }

    /**
     * جلب كل الإعلانات لمنطقة معينة
     *
     * @param int $areaId
     * @return Collection
     */
    public function getAdsByAreaId(int $areaId): Collection
    {
        return Ad::where('area_id', $areaId)
            ->get(['id', 'image']); // فقط الأعمدة المطلوبة
    }


    /**
     * إضافة إعلان جديد لمنطقة معينة
     *
     * @param int $areaId
     * @param string $image
     * @return Ad
     */
    public function addAdToArea(int $areaId, UploadedFile $image): Ad
    {
        // حفظ الصورة داخل storage/app/public/ads
        $path = $image->store('ads', 'public');

        $area = Area::findOrFail($areaId);

        // إنشاء الإعلان وربطه بالمنطقة
        $ad = $area->ads()->create([
            'image' => $path,
        ]);

        return $ad;
    }


    /**
     * حذف إعلان من منطقة معينة
     *
     * @param int $areaId
     * @param int $adId
     * @return bool|null
     */
    public function removeAdFromArea(int $areaId, int $adId): ?bool
    {
        $area = Area::findOrFail($areaId);

        $ad = $area->ads()->findOrFail($adId);
        return $ad->delete();
    }

}

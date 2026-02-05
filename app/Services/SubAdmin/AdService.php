<?php

namespace App\Services\SubAdmin;

    use App\Models\Ad;
    use App\Repositories\Contracts\AdRepositoryInterface;
    use App\Repositories\Eloquent\AdRepository;
    use Illuminate\Database\Eloquent\Collection;
    use Illuminate\Http\UploadedFile;
    use Illuminate\Support\Facades\Storage;

    class AdService
{
    public function __construct(protected AdRepository $repo) {}

    public function getAdsByArea(int $areaId): Collection
    {
        return $this->repo->getAdsByAreaId($areaId);
    }

    public function addAdToArea(int $areaId, UploadedFile $image): array
    {
        $ad = $this->repo->addAdToArea($areaId, $image);

        // نرجع البيانات مع رابط كامل للصورة
        return [
            'id' => $ad->id,
            'image' => url(Storage::url($ad->image)), // رابط كامل
        ];
    }

    public function removeAdFromArea(int $areaId, int $adId): ?bool
    {
        return $this->repo->removeAdFromArea($areaId, $adId);
    }
}



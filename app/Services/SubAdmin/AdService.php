<?php

namespace App\Services\SubAdmin;

    use App\Models\Ad;
    use App\Repositories\Contracts\AdRepositoryInterface;
    use App\Repositories\Eloquent\AdRepository;
    use Illuminate\Database\Eloquent\Collection;
    use Illuminate\Http\UploadedFile;

class AdService
{
    public function __construct(protected AdRepository $repo) {}

    public function getAdsByArea(int $areaId): Collection
    {
        return $this->repo->getAdsByAreaId($areaId);
    }

    public function addAdToArea(int $areaId, string $image): Ad
    {
        return $this->repo->addAdToArea($areaId, $image);
    }

    public function removeAdFromArea(int $areaId, int $adId): ?bool
    {
        return $this->repo->removeAdFromArea($areaId, $adId);
    }
}



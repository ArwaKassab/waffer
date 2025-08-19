<?php

namespace App\Services;

use App\Repositories\Contracts\AdRepositoryInterface;
use Illuminate\Http\UploadedFile;

class AdService
{
    protected AdRepositoryInterface $adRepo;

    public function __construct(AdRepositoryInterface $adRepo)
    {
        $this->adRepo = $adRepo;
    }

    public function storeAd(UploadedFile $image): array
    {
        return $this->adRepo->createWithImage($image);
    }

    public function getLatestAds(): array
    {
        return $this->adRepo->getLatestAds();
    }
}

<?php

namespace App\Repositories\Contracts;

use Illuminate\Http\UploadedFile;

interface AdRepositoryInterface
{
    public function createWithImage(UploadedFile $image): array;
    public function getLatestAds(int $limit = 4): array;
}

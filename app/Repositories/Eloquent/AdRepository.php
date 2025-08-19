<?php

namespace App\Repositories\Eloquent;

use App\Models\Ad;
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
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AdService;

class AdController extends Controller
{
    protected AdService $adService;

    public function __construct(AdService $adService)
    {
        $this->adService = $adService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $ad = $this->adService->storeAd($request->file('image'));

        return response()->json([
            'message' => 'Ad created successfully',
            'data' => $ad
        ]);
    }

    public function latestAds()
    {
        $ads = $this->adService->getLatestAds();

        return response()->json(['data' => $ads]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\StoreService;

class StoreController extends Controller
{
    protected $storeService;

    public function __construct(StoreService $storeService)
    {
        $this->storeService = $storeService;
    }

    public function index(Request $request,$categoryId)
    {
        $areaId = $request->get('area_id');

        if (!$areaId) {
            return response()->json(['message' => 'Area not set'], 400);
        }

        if (!$categoryId) {
            return response()->json(['message' => 'Category not set'], 400);
        }

        $stores = $this->storeService->getStores($areaId, $categoryId);

        return response()->json($stores);
    }

    public function show($id)
    {
        $storeDetails = $this->storeService->getStoreDetails($id);

        if (!$storeDetails) {
            return response()->json(['message' => 'Store not found'], 404);
        }

        return response()->json($storeDetails);
    }



}

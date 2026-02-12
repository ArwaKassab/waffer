<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;
use App\Services\SubAdmin\ProductRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductRequestController extends Controller
{
    protected ProductRequestService $service;

    public function __construct(ProductRequestService $service)
    {
        $this->service = $service;
    }

    public function showCreateRequest(int $id ,Request $request): JsonResponse
    {
        $data = $this->service->getCreateRequestDetails($id , $request->area_id);

        return response()->json([
            'data' => $data
        ]);
    }


    public function indexCreateRequests(): JsonResponse
    {
        $data = $this->service->getAllCreateRequestsForAdmin();

        return response()->json([
            'data' => $data
        ]);
    }
}

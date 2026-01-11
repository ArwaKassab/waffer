<?php

namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ComplaintResource;
use App\Services\SubAdmin\ComplaintService;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function __construct(
        protected ComplaintService $service
    ) {}

    /**
     * عرض جميع الشكاوي (ID + اسم المستخدم + رقمه + نوع الشكوى + تاريخ/وقت)
     * GET /complaints?per_page=20
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;

        $paginator = $this->service->listAll($perPage);

        return ComplaintResource::collection($paginator);
    }

    /**
     * عرض شكوى معينة (نفس السابق + رسالة الشكوى)
     * GET /complaints/{id}
     */
    public function show(int $id)
    {
        $complaint = $this->service->findById($id);

        if (!$complaint) {
            return response()->json(['message' => 'الشكوى غير موجودة.'], 404);
        }

        return new ComplaintResource($complaint);
    }
}

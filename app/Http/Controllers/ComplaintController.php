<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ComplaintService;

class ComplaintController extends Controller
{
    protected $service;

    public function __construct(ComplaintService $service)
    {
        $this->service = $service;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:محتوى الطلب,التوصيل,الدفع,غير ذلك',
            'message' => 'required|string|min:10',
        ]);

        $validated['user_id'] = $request->user()->id;

        $complaint = $this->service->addComplaint($validated);

        return response()->json([
            'message' => 'تم إرسال الشكوى بنجاح',
            'complaint' => $complaint
        ], 201);
    }
}

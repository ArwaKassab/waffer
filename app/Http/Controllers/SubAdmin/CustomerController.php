<?php

// app/Http/Controllers/SubAdmin/UserController.php
namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerSubAdminResource;
use App\Models\User;
use App\Services\CustomerService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }
    /**
     * GET /sub_admin/users
     */
    public function index(Request $request)
    {
        $request->validate(['per_page' => 'nullable|integer|min:1|max:100']);
        $perPage  = (int) $request->input('per_page', 15);
        $areaId   = (int) $request->user()->area_id;

        $paginator = $this->customerService->listCustomersForSubAdminAreaPaginated($areaId, $perPage);

        return CustomerSubAdminResource::collection($paginator);
    }

    // بحث بالاسم (بداية الكلمة)
    public function searchByName(Request $request)
    {
        $request->validate([
            'q'        => 'required|string|min:2',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $areaId  = (int) $request->input('area_id');
        $perPage = (int) $request->input('per_page', 15);
        $q       = $request->input('q');

        $paginator = $this->customerService->searchByNamePrefix($areaId, $q, $perPage);

        return CustomerSubAdminResource::collection($paginator);
    }

    // بحث بالهاتف (بداية الرقم)
    public function searchByPhone(Request $request)
    {
        $request->validate([
            'q'        => 'required|string|min:2',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $areaId  = (int) $request->input('area_id');
        $perPage = (int) $request->input('per_page', 15);
        $q       = $request->input('q');

        $paginator = $this->customerService->searchByPhonePrefix($areaId, $q, $perPage);

        return CustomerSubAdminResource::collection($paginator);
    }
}

<?php

// app/Http/Controllers/SubAdmin/UserController.php
namespace App\Http\Controllers\SubAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AddressResource;
use App\Http\Resources\BannedUserResource;
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

//استرجاع يدوي لحساب محذوف عبر الدعم
    public function adminRestoreByPhone(string $canonical00963Phone)
    {
        $user = \App\Models\User::onlyTrashed()
            ->where('phone_shadow', $canonical00963Phone)
            ->first();

        if (!$user) {
            abort(404, 'لا يوجد حساب محذوف بهذا الرقم.');
        }

        // أعيدي الهاتف الحقيقي
        $user->phone = $user->phone_shadow;
        $user->phone_shadow = null;
        $user->status = true;

        $user->restore();
        $user->save();

        return response()->json(['message' => 'تم استرجاع الحساب.']);
    }


    /**
     * GET /sub_admin/users/{user}/addresses
     * ?include_deleted=0
     */
    public function addresses(Request $request, int $user)
    {

        $subAdminAreaId = (int) $request->area_id;

        $items = $this->customerService
            ->listCustomerAddressesForSubAdminNoPaginate($user, $subAdminAreaId);

        return AddressResource::collection($items);

    }

    public function banned(Request $request)
    {
        $areaId = (int) $request->user()->area_id;

        $paginator = $this->customerService
            ->listBannedCustomersForSubAdminPaginated($areaId);

        return BannedUserResource::collection($paginator);
    }

    public function setOrToggleBan(Request $request, int $user)
    {
        $request->validate([
            'banned' => 'sometimes|boolean',
        ]);

        $areaId  = (int) $request->user()->area_id;
        $desired = $request->has('banned') ? (bool) $request->boolean('banned') : null; // null => toggle

        [$changed, $nowBanned] = $this->customerService
            ->setOrToggleBanInArea($user, $areaId, $desired);

        if (!$changed) {
            return response()->json([
                'message' => $nowBanned ? 'المستخدم محظور مسبقًا.' : 'المستخدم غير محظور أصلًا.',
                'banned'  => $nowBanned,
            ]);
        }

        return response()->json([
            'message' => $nowBanned ? 'تم حظر المستخدم.' : 'تم رفع الحظر عن المستخدم.',
            'banned'  => $nowBanned,
        ]);
    }




}

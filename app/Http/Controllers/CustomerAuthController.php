<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginCustomerRequest;
use App\Http\Requests\RegisterCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Address;
use App\Models\User;
use App\Services\AddressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\CustomerAuthService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CustomerAuthController extends Controller
{
    protected $customerService ;
    protected $userRepo;
    public function __construct(CustomerAuthService $CustomerService , UserRepositoryInterface $userRepo)
    {
        $this->userRepo = $userRepo;
        $this->customerService = $CustomerService;
    }

    public function register(RegisterCustomerRequest $request)
    {
        try {
            // جلب visitor_id من الكوكيز (أو من الطلب)
            $visitorId = $request->cookie('visitor_id');

            $user = $this->customerService->registerCustomer($request->validated(), $visitorId);

            return response()->json([
                'message' => 'تم تسجيل العميل بنجاح',
                'user'    => new CustomerResource($user),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء تسجيل العميل',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function login(LoginCustomerRequest $request)
    {
        $user = $this->userRepo->findByPhoneAndType($request->phone, 'customer');

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 401);
        }
//        $this->migrateGuestCartToUser($request->session()->getId(), $user->id);
        $token = $user->createToken('customer-token', ['customer'])->plainTextToken;

        return response()->json(['token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
    }




}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginCustomerRequest;
use App\Http\Requests\RegisterCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\UserService;

class CustomerAuthController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function register(RegisterCustomerRequest $request)
    {
        try {
            $user = $this->userService->registerCustomer($request->validated());

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

        $token = $user->createToken('customer-token', ['customer'])->plainTextToken;

        return response()->json(['token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
    }
}

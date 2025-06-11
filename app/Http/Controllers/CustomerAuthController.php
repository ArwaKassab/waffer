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
use App\Services\UserService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CustomerAuthController extends Controller
{
    protected $userService ;
    protected $userRepo;
    public function __construct(UserService $userService ,UserRepositoryInterface $userRepo)
    {
        $this->userRepo = $userRepo;
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
//        $this->migrateGuestCartToUser($request->session()->getId(), $user->id);
        $token = $user->createToken('customer-token', ['customer'])->plainTextToken;

        return response()->json(['token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
    }

    public function sendResetPasswordCode(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $response = $this->userService->sendResetPasswordCode($request->phone);

        if (!$response['success']) {
            return response()->json(['message' => $response['message']], $response['status']);
        }

        return response()->json([
            'message' => $response['message'],
            'message2front'=> $response['message2front'],
            'otp' => $response['otp'], // مؤقتاً ل اختبار الـ OTP
        ]);
    }

    public function verifyResetPasswordCode(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|string',
        ]);

        $response = $this->userService->verifyResetPasswordCode($request->phone, $request->otp);

        if (!$response['success']) {
            return response()->json(['message' => $response['message']], 400);
        }

        return response()->json([
            'message' => $response['message'],
            'message2front'=> $response['message2front'],
            'temp_token' => $response['temp_token'],
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'بيانات ناقصة أو غير صحيحة',
                'errors' => $validator->errors(),
            ], 422);
        }

        $authHeader = $request->header('Authorization');

        if (!$authHeader || !\Illuminate\Support\Str::startsWith($authHeader, 'Bearer ')) {
            return response()->json(['message' => 'رمز التحقق مفقود'], 401);
        }

        $token = \Illuminate\Support\Str::after($authHeader, 'Bearer ');

        $response = $this->userService->resetPassword($request->phone, $request->new_password, $token);

        if (!$response['success']) {
            return response()->json(['message' => $response['message']], 400);
        }

        return response()->json(['message' => $response['message']]);
    }



//    public function migrateGuestCartToUser($sessionId, $userId)
//    {
//        $cartItems = Redis::get("guest_cart:{$sessionId}");
//
//        if ($cartItems) {
//            $items = json_decode($cartItems, true);
//
//            foreach ($items as $item) {
//                CartItem::create([
//                    'user_id'    => $userId,
//                    'product_id' => $item['product_id'],
//                    'quantity'   => $item['quantity'],
//                    'price'      => $item['price'],
//                ]);
//            }
//
//            Redis::del("guest_cart:{$sessionId}");
//        }
//    }

}

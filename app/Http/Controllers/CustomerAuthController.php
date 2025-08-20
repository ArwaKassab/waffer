<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginCustomerRequest;
use App\Http\Requests\RegisterCustomerRequest;
use App\Http\Requests\StartRegisterRequest;
use App\Http\Requests\VerifyRegisterRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Address;
use App\Models\User;
use App\Services\AddressService;
use App\Services\SmsChefOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\CustomerAuthService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CustomerAuthController extends Controller
{
    protected $customerService;
    protected $userRepo;
    protected SmsChefOtpService $otpService;


    public function __construct(SmsChefOtpService $otpService ,CustomerAuthService $CustomerService, UserRepositoryInterface $userRepo)
    {
        $this->userRepo = $userRepo;
        $this->customerService = $CustomerService;
        $this->otpService = $otpService;
    }

    /**
     * الخطوة 1: استقبال بيانات التسجيل، تخزين مؤقت + إرسال OTP من الجهاز
     */
    public function startRegister(StartRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $visitorId = $request->cookie('visitor_id');

        // ✅ خليه يدخل 09xxxxxxxx – ونحوّله داخلياً إلى 00963xxxxxxxxx
        $data['phone'] = $this->normalizeCanonical00963($data['phone']);

        $key = 'register-start:' . sha1(($data['phone'] ?? '') . '|' . $request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'message' => 'محاولات كثيرة. حاول لاحقًا.',
                'retry_after_seconds' => RateLimiter::availableIn($key),
            ], 429);
        }
        RateLimiter::hit($key, 60);

        if (User::where('phone', $data['phone'])->exists()) {
            return response()->json(['message' => 'الرقم مستخدم مسبقًا.'], 422);
        }

        [$tempId, $sendMeta] = $this->customerService->startRegistration($data, $visitorId);

        return response()->json([
            'message' => 'تم إرسال رمز التفعيل إلى هاتفك.',
            'verification_step' => 'otp_required',
            'temp_id' => $tempId,
            'phone' => $data['phone'],
            'debug_smschef' => $sendMeta,
        ], 200);
    }

    /**
     * الخطوة 2: التحقق من OTP ثم إنشاء الحساب فعليًا
     */
    public function verifyRegister(VerifyRegisterRequest $request): JsonResponse
    {
        $tempId = $request->input('temp_id');

        // ✅ أيضاً يدخله 09xxxxxxxx ونحوّله للصيغة الداخلية 00963...
        $phone  = $this->normalizeCanonical00963($request->input('phone'));
        $otp    = $request->input('otp');

        $user = $this->customerService->finalizeRegistration($tempId, $phone, $otp);

        return response()->json([
            'message' => 'تم إنشاء حسابك وتفعيل رقم الهاتف بنجاح.',
            'user'    => new CustomerResource($user),
        ], 201);
    }

    /** ======================
     *  Helpers – تطبيع الأرقام
     *  ====================== */

    /**
     * الصيغة الداخلية القياسية للتخزين/المقارنة: 00963xxxxxxxxx
     * تقبل: 09xxxxxxxx أو +963xxxxxxxxx أو 963xxxxxxxxx أو 00963xxxxxxxxx
     */
    private function normalizeCanonical00963(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone); // أرقام فقط

        // يبدأ بـ 00963
        if (str_starts_with($digits, '00963')) {
            return $digits;
        }

        // يبدأ بـ +963 أو 963
        if (str_starts_with($digits, '963')) {
            return '00963' . substr($digits, 3); // 963XXXXXXXXX → 00963XXXXXXXXX
        }

        // يبدأ بـ 0 محلي (09xxxxxxxx)
        if (str_starts_with($digits, '0')) {
            return '00963' . substr($digits, 1);
        }

        // fallback: لو فقط 9 خانات بدون مقدّم
        if (strlen($digits) === 9) {
            return '00963' . $digits;
        }

        // آخر حل: لو ما قدر يطابق، رجّع كما هو
        return $digits;
    }



    public function login(LoginCustomerRequest $request)
    {
        $user = $this->userRepo->findByPhoneAndType($request->phone);

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

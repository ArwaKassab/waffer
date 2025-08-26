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


    public function __construct(SmsChefOtpService $otpService ,CustomerAuthService $CustomerService,
                                UserRepositoryInterface $userRepo)
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

        // ✅ رجّع حالة واضحة للفرونتند
        if (!empty($sendMeta['ok'])) {
            return response()->json([
                'message'            => 'تمت جدولة إرسال رمز التفعيل.',
                'verification_step'  => 'otp_pending',
                'temp_id'            => $tempId,
                'phone'              => $data['phone'],
//                'sent'               => true,
//                'message_id'         => $sendMeta['message_id'] ?? null,
            ], 200);
        }

        return response()->json([
            'message'   => 'تعذّر إرسال رمز التفعيل حاليًا. حاول بعد قليل.',
            'sent'      => false,
            'temp_id'   => $tempId,
            'phone'     => $data['phone'],
        ], 502);
    }


    /**
     * الخطوة 2: التحقق من OTP ثم إنشاء الحساب فعليًا
     */
    public function verifyRegister(VerifyRegisterRequest $request): JsonResponse
    {
        $tempId = $request->input('temp_id');

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
     * تقبل: 09xxxxxxxx
     */
    private function normalizeCanonical00963(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone); // أرقام فقط

        // نقبل فقط 09xxxxxxxx في التسجيل (القواعد أعلاه تضمن هذا)
        if (preg_match('/^09\d{8}$/', $digits)) {
            return '00963' . substr($digits, 1); // حذف الـ 0 واستبدالها بـ 00963
        }

        // إن وصل بصيغة مخزّنة مسبقًا
        if (preg_match('/^00963\d{9}$/', $digits)) {
            return $digits;
        }

        // أي صيغة أخرى نرفضها (لأننا اشترطنا 09 في التسجيل)
        throw new \InvalidArgumentException('صيغة رقم غير مسموح بها.');
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

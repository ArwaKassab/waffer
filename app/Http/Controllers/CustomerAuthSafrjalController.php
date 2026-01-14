<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginCustomerRequest;
use App\Http\Requests\ResendRegisterOtpRequest;
use App\Http\Requests\StartRegisterRequest;
use App\Http\Requests\VerifyRegisterRequest;
use App\Http\Resources\CustomerResource;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\CustomerAuthSafrjalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class CustomerAuthSafrjalController extends Controller
{
    protected CustomerAuthSafrjalService $customerService;
    protected UserRepositoryInterface $userRepo;

    public function __construct(CustomerAuthSafrjalService $customerService, UserRepositoryInterface $userRepo)
    {
        $this->customerService = $customerService;
        $this->userRepo = $userRepo;
    }

    public function startRegister(StartRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $visitorId = $request->cookie('visitor_id');

        $data['phone'] = $this->normalizeCanonical00963($data['phone']);

        $key = 'register-start-safrjal:' . sha1(($data['phone'] ?? '') . '|' . $request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'message' => 'محاولات كثيرة. حاول لاحقًا.',
                'retry_after_seconds' => RateLimiter::availableIn($key),
            ], 429);
        }
        RateLimiter::hit($key, 60);

        if (User::where('phone', $data['phone'])->where('type', 'customer')->exists()) {
            return response()->json(['message' => 'الرقم مستخدم مسبقًا.'], 422);
        }

        [$tempId, $sendMeta] = $this->customerService->startRegistration($data, $visitorId);

        if (!empty($sendMeta['ok'])) {
            return response()->json([
                'message'           => 'تمت جدولة إرسال رمز التفعيل عبر واتساب.',
                'verification_step' => 'otp_pending',
                'temp_id'           => $tempId,
                'phone'             => $data['phone'],
            ], 200);
        }

        return response()->json([
            'message' => 'تعذّر إرسال رمز التفعيل حاليًا. حاول بعد قليل.',
            'sent'    => false,
            'temp_id' => $tempId,
            'phone'   => $data['phone'],
        ], 502);
    }

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

    public function resendRegisterOtp(ResendRegisterOtpRequest $request): JsonResponse
    {
        $tempId = $request->input('temp_id');
        $phone  = $this->normalizeCanonical00963($request->input('phone'));

        $key = 'register-resend-safrjal:' . sha1($phone . '|' . $request->ip());
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json([
                'message' => 'محاولات كثيرة لإعادة الإرسال. حاول لاحقًا.',
                'retry_after_seconds' => RateLimiter::availableIn($key),
            ], 429);
        }
        RateLimiter::hit($key, 60);

        try {
            $sendMeta = $this->customerService->resendRegistrationOtp($tempId, $phone);

            if (!empty($sendMeta['ok'])) {
                return response()->json([
                    'message'           => 'تمت جدولة إعادة إرسال رمز التفعيل عبر واتساب.',
                    'verification_step' => 'otp_pending',
                    'temp_id'           => $tempId,
                    'phone'             => $phone,
                ], 200);
            }

            return response()->json([
                'message' => 'تعذّر إعادة إرسال الرمز حاليًا. حاول بعد قليل.',
                'temp_id' => $tempId,
                'phone'   => $phone,
            ], 502);

        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 410);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'حدث خطأ غير متوقع.'], 500);
        }
    }

    // login/logout نفس القديم تمامًا إذا بدك (لا علاقة له بالـ OTP)
    public function login(LoginCustomerRequest $request)
    {
        $user  = $this->userRepo->findByPhoneAndType($request->phone, 'customer');

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

    private function normalizeCanonical00963(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (preg_match('/^09\d{8}$/', $digits)) {
            return '00963' . substr($digits, 1);
        }

        if (preg_match('/^00963\d{9}$/', $digits)) {
            return $digits;
        }

        throw new \InvalidArgumentException('صيغة رقم غير مسموح بها.');
    }
}

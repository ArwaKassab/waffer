<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\SmsChefOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AuthResetController extends Controller
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private SmsChefOtpService $otpService,
    ) {}

    /**
     * الخطوة 1: إرسال رمز إعادة التعيين
     */
    public function sendResetPasswordCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
        ]);

        $phone = $this->normalizeCanonical00963($data['phone']);


        $user = $this->userRepo->findByPhoneAndType($phone);
        if (!$user) {
            return response()->json([
                'message' => 'إن وُجد حساب مطابق سيتم إرسال رمز إعادة التعيين.',
            ]);
        }

        // Rate limit
        $key = 'reset-send:' . sha1($phone . '|' . $request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'message' => 'محاولات كثيرة. حاول لاحقًا.',
                'retry_after_seconds' => RateLimiter::availableIn($key),
            ], 429);
        }
        RateLimiter::hit($key, 60); // محاولة كل 60 ثانية


        $tempId = (string) Str::uuid();
        $otpPlain = $this->otpService->generateOtp(6);
        $payload = [
            'phone'    => $phone,
            'otp_hash' => Hash::make($otpPlain),
        ];


        $encrypted = Crypt::encryptString(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $ttlSeconds = (int) config('services.smschef.expire', 300) + 120; // 5 دقائق + سماح
        Cache::put($this->cacheKeyPending($tempId), $encrypted, $ttlSeconds);


        $phoneForSms = $this->toSmsE164PlusFromCanonical($phone);
        $sendMeta = $this->otpService->sendOtpViaDevice(
            $phoneForSms,
            $otpPlain,
            'رمز إعادة تعيين كلمة المرور'
        );

        if (!empty($sendMeta['ok'])) {
            return response()->json([
                'message'           => 'تمت جدولة إرسال رمز إعادة التعيين.',
                'verification_step' => 'otp_pending',
                'temp_id'           => $tempId,
                'phone'             => $phone,
            ], 200);
        }

        return response()->json([
            'message' => 'تعذّر إرسال الرمز حاليًا. حاول بعد قليل.',
        ], 502);
    }

    /**
     * الخطوة 2: التحقق من OTP
     * - عند النجاح نولّد Reset Token قصير العمر ونخزّنه، ونرجّعه للفرونت.
     */
    public function verifyResetPasswordCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'temp_id' => ['required', 'string'],
            'phone'   => ['required', 'string'],
            'otp'     => ['required', 'string'],
        ]);

        $tempId = $data['temp_id'];
        $phoneInput = $this->normalizeCanonical00963($data['phone']);
        $otpInput = $data['otp'];

        $encrypted = Cache::get($this->cacheKeyPending($tempId));
        if (!$encrypted) {
            return response()->json(['message' => 'انتهت صلاحية الجلسة. أعد الإرسال.'], 410);
        }

        $json = Crypt::decryptString($encrypted);
        $session = json_decode($json, true) ?: [];

        $storedPhone = $session['phone'] ?? '';
        if ($storedPhone !== $phoneInput) {
            return response()->json(['message' => 'البيانات لا تتطابق مع الجلسة.'], 409);
        }

        if (empty($session['otp_hash']) || !Hash::check($otpInput, $session['otp_hash'])) {
            return response()->json(['message' => 'رمز OTP غير صحيح.'], 400);
        }


        $resetToken = Str::random(64);


        $ttlSeconds = 5 * 60;
        Cache::put($this->cacheKeyPassed($tempId), json_encode([
            'phone' => $storedPhone,
            'reset_token' => $resetToken,
        ], JSON_UNESCAPED_UNICODE), $ttlSeconds);


        Cache::forget($this->cacheKeyPending($tempId));

        return response()->json([
            'message'     => 'تم التحقق من الرمز بنجاح.',
            'temp_id'     => $tempId,
            'reset_token' => $resetToken,
        ], 200);
    }

    /**
     * الخطوة 3: تغيير كلمة المرور (يتطلب الميدلوير للتحقق من reset_token)
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'temp_id'  => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
            // أرسل password_confirmation أيضًا
        ]);

        $tempId = $data['temp_id'];
        $passed = Cache::get($this->cacheKeyPassed($tempId));
        if (!$passed) {
            return response()->json(['message' => 'انتهت صلاحية رابط إعادة التعيين.'], 410);
        }

        $session = json_decode($passed, true) ?: [];
        $phone = $session['phone'] ?? null;

        if (!$phone) {
            return response()->json(['message' => 'جلسة غير صالحة.'], 400);
        }

        $user = $this->userRepo->findByPhoneAndType($phone);
        if (!$user) {
            return response()->json(['message' => 'الحساب غير موجود.'], 404);
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }
        Cache::forget($this->cacheKeyPassed($tempId));

        return response()->json(['message' => 'تم تحديث كلمة المرور بنجاح.'], 200);
    }

    /* ========================
     * Helpers – أرقام + مفاتيح
     * ======================== */

    private function normalizeCanonical00963(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (preg_match('/^00963\d{9}$/', $digits)) {
            return $digits;
        }
        if (preg_match('/^09\d{8}$/', $digits)) {
            return '00963' . substr($digits, 1);
        }
        throw ValidationException::withMessages(['phone' => 'صيغة رقم غير مسموح بها.']);
    }

    private function toSmsE164PlusFromCanonical(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (preg_match('/^00963\d{9}$/', $digits)) {
            return '+963' . substr($digits, 5);
        }
        if (preg_match('/^09\d{8}$/', $digits)) {
            return '+963' . substr($digits, 1);
        }
        if (preg_match('/^\+963\d{9}$/', $phone)) {
            return $phone;
        }
        throw ValidationException::withMessages(['phone' => 'صيغة رقم غير صالحة للإرسال.']);
    }

    private function cacheKeyPending(string $tempId): string
    {
        return 'reset:pending:' . $tempId;
    }

    private function cacheKeyPassed(string $tempId): string
    {
        return 'reset:passed:' . $tempId;
    }
}

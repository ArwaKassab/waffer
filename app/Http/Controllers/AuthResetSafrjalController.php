<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\SafrjalOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AuthResetSafrjalController extends Controller
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private SafrjalOtpService $otpService,
    ) {}

    /**
     * الخطوة 1: إرسال رمز إعادة التعيين (Fake Success إذا لم يوجد حساب)
     * نفس Response على الفرونت.
     */
    public function sendResetPasswordCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
        ]);

        $phone = $this->normalizeCanonical00963($data['phone']);

        // Rate limit (لا نربطه بوجود المستخدم لمنع كشف الحساب)
        $key = 'reset-send:' . sha1($phone . '|' . $request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'message' => 'محاولات كثيرة. حاول لاحقًا.',
                'retry_after_seconds' => RateLimiter::availableIn($key),
            ], 429);
        }
        RateLimiter::hit($key, 60);

        // توليد temp_id + OTP + جلسة Pending دائماً (حتى لو المستخدم غير موجود)
        $tempId  = (string) Str::uuid();
        $otpPlain = $this->generateOtp(6);

        $payload = [
            'phone'         => $phone,
            'otp_hash'      => Hash::make($otpPlain),
            'resend_count'  => 0,
            'is_fake'       => false,
        ];

        // تحقّق من وجود المستخدم (لكن لا نغير response)
        $user = $this->userRepo->findByPhoneAndType($phone, 'customer');
        if (!$user) {
            // Fake session: لا إرسال
            $payload['is_fake'] = true;

            $this->storePendingSession($tempId, $payload);

            // نفس شكل نجاح الإرسال تمامًا (بدون تسريب وجود الحساب)
            return response()->json([
                'message'           => 'تمت جدولة إرسال رمز إعادة التعيين.',
                'verification_step' => 'otp_pending',
                'temp_id'           => $tempId,
                'phone'             => $phone,
            ], 200);
        }

        // المستخدم موجود: خزّني الجلسة ثم ارسلي عبر Safrjal
        $this->storePendingSession($tempId, $payload);

        try {
            $phoneForSafrjal = $this->toSafrjalInternationalNoPlusFromCanonical($phone);

            $this->otpService->sendOtp(
                $phoneForSafrjal,
                $otpPlain,
                'رمز إعادة تعيين كلمة المرور'
            );

            return response()->json([
                'message'           => 'تمت جدولة إرسال رمز إعادة التعيين.',
                'verification_step' => 'otp_pending',
                'temp_id'           => $tempId,
                'phone'             => $phone,
            ], 200);

        } catch (\Throwable $e) {
            // لا تمسحي الجلسة: ممكن المستخدم يطلب resend
            return response()->json([
                'message' => 'تعذّر إرسال الرمز حاليًا. حاول بعد قليل.',
            ], 502);
        }
    }

    /**
     * الخطوة 2: التحقق من OTP
     * عند النجاح نولّد reset_token ونخزّنه بنفس الطريقة القديمة (Plain) حتى لا يتغير شيء على الفرونت/الميدلوير.
     */
    public function verifyResetPasswordCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'temp_id' => ['required', 'string'],
            'phone'   => ['required', 'string'],
            'otp'     => ['required', 'string'],
        ]);

        $tempId     = $data['temp_id'];
        $phoneInput = $this->normalizeCanonical00963($data['phone']);
        $otpInput   = (string) $data['otp'];

        $encrypted = Cache::get($this->cacheKeyPending($tempId));
        if (!$encrypted) {
            return response()->json(['message' => 'انتهت صلاحية الجلسة. أعد الإرسال.'], 410);
        }

        $session = json_decode(Crypt::decryptString($encrypted), true) ?: [];

        $storedPhone = $session['phone'] ?? '';
        if ($storedPhone !== $phoneInput) {
            return response()->json(['message' => 'البيانات لا تتطابق مع الجلسة.'], 409);
        }

        // Fake session: دائماً فشل تحقق OTP (بدون كشف السبب الحقيقي)
        if (!empty($session['is_fake'])) {
            return response()->json(['message' => 'رمز OTP غير صحيح.'], 400);
        }

        if (empty($session['otp_hash']) || !Hash::check($otpInput, (string) $session['otp_hash'])) {
            return response()->json(['message' => 'رمز OTP غير صحيح.'], 400);
        }

        $resetToken = Str::random(64);

        // نفس كودك القديم: تخزين passed session كنص JSON (بدون تغيير الميدلوير عندك)
        $ttlSeconds = 5 * 60;
        Cache::put($this->cacheKeyPassed($tempId), json_encode([
            'phone'       => $storedPhone,
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
     * الخطوة 3: تغيير كلمة المرور (بدون تغيير عقد الفرونت)
     * يفترض وجود ميدلوير تتحقق من reset_token كما عندك.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'temp_id'  => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ]);

        $tempId = $data['temp_id'];

        $passed = Cache::get($this->cacheKeyPassed($tempId));
        if (!$passed) {
            return response()->json(['message' => 'انتهت صلاحية رابط إعادة التعيين.'], 410);
        }

        $session = json_decode($passed, true) ?: [];
        $phone   = $session['phone'] ?? null;

        if (!$phone) {
            return response()->json(['message' => 'جلسة غير صالحة.'], 400);
        }

        $user = $this->userRepo->findByPhoneAndType($phone, 'customer');
        if (!$user) {
            // حتى لو حصل passed session بشكل غير متوقع، لا تغيّر الرسالة كثيرًا
            return response()->json(['message' => 'الحساب غير موجود.'], 404);
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        // تسجيل خروج من كل الأجهزة (كما عندك)
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }

        DeviceToken::where('user_id', $user->id)->delete();

        Cache::forget($this->cacheKeyPassed($tempId));

        return response()->json(['message' => 'تم تحديث كلمة المرور وتم تسجيل خروجك من جميع الأجهزة.'], 200);
    }

    /**
     * إعادة إرسال رمز OTP (Fake Success إذا كانت الجلسة fake)
     * نفس Response على الفرونت.
     */
    public function resendResetPasswordCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'temp_id' => ['required', 'string'],
        ]);

        $tempId     = $data['temp_id'];
        $pendingKey = $this->cacheKeyPending($tempId);

        $encrypted = Cache::get($pendingKey);
        if (!$encrypted) {
            return response()->json([
                'message' => 'انتهت صلاحية الجلسة. ابدأ من جديد.',
            ], 410);
        }

        $session = json_decode(Crypt::decryptString($encrypted), true) ?: [];
        $phone   = $session['phone'] ?? null;

        if (!$phone) {
            return response()->json([
                'message' => 'جلسة غير صالحة (لا يوجد هاتف مخزَّن).',
            ], 400);
        }

        // Rate limit لإعادة الإرسال (لا نربطه بوجود المستخدم)
        $rlKey = 'reset-resend:' . sha1($phone . '|' . $request->ip());
        if (RateLimiter::tooManyAttempts($rlKey, 5)) {
            return response()->json([
                'message'             => 'محاولات كثيرة. حاول لاحقًا.',
                'retry_after_seconds' => RateLimiter::availableIn($rlKey),
            ], 429);
        }
        RateLimiter::hit($rlKey, 60);

        // حد أعلى للـ resend داخل نفس الجلسة
        $maxResends = 5;
        $resends    = (int) ($session['resend_count'] ?? 0);
        if ($resends >= $maxResends) {
            return response()->json([
                'message' => 'تم تجاوز الحد المسموح لإعادة الإرسال.',
            ], 429);
        }

        // OTP جديد + تحديث session
        $otpPlain                = $this->generateOtp(6);
        $session['otp_hash']     = Hash::make($otpPlain);
        $session['resend_count'] = $resends + 1;

        // حفظ الجلسة بنفس TTL
        $this->storePendingSession($tempId, $session);

        // Fake session: لا إرسال، لكن نفس Response نجاح
        if (!empty($session['is_fake'])) {
            return response()->json([
                'message'           => 'تمت إعادة إرسال الرمز.',
                'verification_step' => 'otp_pending',
                'temp_id'           => $tempId,
            ], 200);
        }

        // إرسال فعلي عبر Safrjal
        try {
            $phoneForSafrjal = $this->toSafrjalInternationalNoPlusFromCanonical($phone);

            $this->otpService->sendOtp(
                $phoneForSafrjal,
                $otpPlain,
                'رمز إعادة تعيين كلمة المرور (إعادة إرسال)'
            );

            return response()->json([
                'message'           => 'تمت إعادة إرسال الرمز.',
                'verification_step' => 'otp_pending',
                'temp_id'           => $tempId,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'تعذّر إرسال الرمز حاليًا. حاول بعد قليل.',
            ], 502);
        }
    }

    /* ========================
     * Helpers
     * ======================== */

    private function storePendingSession(string $tempId, array $payload): void
    {
        $encrypted = Crypt::encryptString(json_encode($payload, JSON_UNESCAPED_UNICODE));

        // TTL من Safrjal (وإن لم يوجد fallback 300)
        $ttlSeconds = (int) config('services.safrjal.expire', 300) + 120;

        Cache::put($this->cacheKeyPending($tempId), $encrypted, $ttlSeconds);
    }

    private function generateOtp(int $length = 6): string
    {
        $min = (int) ('1' . str_repeat('0', $length - 1));
        $max = (int) str_repeat('9', $length);
        return (string) random_int($min, $max);
    }

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

    /**
     * Safrjal expects international digits WITHOUT plus (example: 962..., 963...)
     * input stored: 00963xxxxxxxxx -> output: 963xxxxxxxxx
     */
    private function toSafrjalInternationalNoPlusFromCanonical(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (preg_match('/^00963\d{9}$/', $digits)) {
            return '963' . substr($digits, 5);
        }

        if (preg_match('/^09\d{8}$/', $digits)) {
            return '963' . substr($digits, 1);
        }

        if (preg_match('/^963\d{9}$/', $digits)) {
            return $digits;
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

<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\CartRepository;
use App\Services\AddressService;
use App\Services\RedisCartService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class CustomerAuthService
{
    protected UserRepositoryInterface $userRepo;
    protected AddressService $addressService;
    protected RedisCartService $redisCartService;
    protected CartRepository $cartRepo;
    protected SmsChefOtpService $otpService;

    public function __construct(SmsChefOtpService $otpService, UserRepositoryInterface $userRepo, AddressService $addressService, RedisCartService $redisCartService, CartRepository $cartRepo)
    {
        $this->userRepo = $userRepo;
        $this->addressService = $addressService;
        $this->redisCartService = $redisCartService;
        $this->cartRepo = $cartRepo;
        $this->otpService = $otpService;

    }

    /**
     * 1) بدء التسجيل: نخزّن البيانات مؤقتًا، نرسل OTP، ونرجع temp_id.
     */
    public function startRegistration(array $data, ?string $visitorId = null): array
    {
        $tempId = (string)Str::uuid();

        $data['phone'] = $this->normalizeCanonical00963Local($data['phone'] ?? '');

        $payload = $data;
        $payload['password'] =Hash::make($data['password']);
        $payload['type'] = 'customer';
        $payload['visitor_id'] = $visitorId;

        $otpPlain = app(SmsChefOtpService::class)->generateOtp(6);
        $payload['otp_hash'] = Hash::make($otpPlain);

        $encrypted = Crypt::encryptString(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $ttlSeconds = (int)config('services.smschef.expire', 300) + 120;
        Cache::put($this->cacheKey($tempId), $encrypted, $ttlSeconds);

        // ✅ عند الإرسال للمزوّد نحول الصيغة الداخلية (00963...) إلى صيغة الإرسال (+963...)
        $phoneForSms = $this->toSmsE164PlusFromCanonical($data['phone']);

        $sendMeta = app(SmsChefOtpService::class)->sendOtpViaDevice(
            $phoneForSms,
            $otpPlain,
            'رمز تفعيل حسابك'
        );

        return [$tempId, $sendMeta];
    }

    public function finalizeRegistration(string $tempId, string $phone, string $otpInput): \App\Models\User
    {
        $encrypted = Cache::pull($this->cacheKey($tempId));
        if (!$encrypted) {
            throw new \RuntimeException('انتهت صلاحية جلسة التسجيل. أعد المحاولة.');
        }

        $json = Crypt::decryptString($encrypted);
        $data = json_decode($json, true) ?: [];

        // ✅ طبّعي الرقمين للصيغة الداخلية 00963... قبل المقارنة
        $storedPhone = $this->normalizeCanonical00963Local($data['phone'] ?? '');
        $inputPhone = $this->normalizeCanonical00963Local($phone);

        if ($storedPhone === '' || $storedPhone !== $inputPhone) {
            Cache::put($this->cacheKey($tempId), $encrypted, 120);
            throw new \RuntimeException('بيانات التسجيل غير متطابقة.');
        }

        // تحقق OTP المحلي
        if (empty($data['otp_hash']) || !Hash::check($otpInput, $data['otp_hash'])) {
            Cache::put($this->cacheKey($tempId), $encrypted, 120);
            throw new \RuntimeException('رمز التفعيل غير صحيح.');
        }

        DB::beginTransaction();
        try {
            $user = $this->userRepo->create([
                'name' => $data['name'],
                'phone' => $storedPhone,
                'password' => $data['password'],
                'type' => $data['type'] ?? 'customer',
                'area_id' => $data['area_id'],

            ]);

            $this->addressService->createAddressForUser($user->id, [
                'area_id' => $data['area_id'],
                'address_details' => $data['address_details'],
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'title' => $data['title'],
            ]);

            $userCart = $this->cartRepo->getCartByUserId($user->id);
            if (!$userCart) {
                $userCart = $this->cartRepo->createCartForUser($user->id);
            }

            if (!empty($data['visitor_id'])) {
                $this->redisCartService->migrateVisitorCartToUserCart($data['visitor_id'], $user->id);
            }

            $user->forceFill(['phone_verified_at' => now()])->save();

            DB::commit();
            return $user;

        } catch (\Exception $e) {
            DB::rollBack();
            Cache::put($this->cacheKey($tempId), $encrypted, 180);
            throw $e;
        }
    }

    public function resendRegistrationOtp(string $tempId, string $phone): array
    {
        $cacheKey  = $this->cacheKey($tempId);
        $encrypted = Cache::get($cacheKey);

        if (!$encrypted) {
            throw new \RuntimeException('انتهت صلاحية جلسة التسجيل. أعد البدء.');
        }

        $json  = Crypt::decryptString($encrypted);
        $data  = json_decode($json, true) ?: [];

        // طَبّع الرقمين للصيغة الداخلية وقارِن
        $storedPhone = $this->normalizeCanonical00963Local($data['phone'] ?? '');
        $inputPhone  = $this->normalizeCanonical00963Local($phone);

        if ($storedPhone === '' || $storedPhone !== $inputPhone) {
            throw new \RuntimeException('رقم الهاتف لا يطابق الجلسة الحالية.');
        }

        // توليد OTP جديد وتحديث الهاش
        $otpPlain          = app(SmsChefOtpService::class)->generateOtp(6);
        $data['otp_hash']  = Hash::make($otpPlain);

        // أعِد تشفير + تخزين (تجديد TTL)
        $encryptedNew = Crypt::encryptString(json_encode($data, JSON_UNESCAPED_UNICODE));
        $ttlSeconds   = (int)config('services.smschef.expire', 300) + 120;
        Cache::put($cacheKey, $encryptedNew, $ttlSeconds);

        // أرسِل عبر الجهاز: 00963… → +963…
        $phoneForSms = $this->toSmsE164PlusFromCanonical($storedPhone);

        return app(SmsChefOtpService::class)->sendOtpViaDevice(
            $phoneForSms,
            $otpPlain,
            'رمز تفعيل حسابك'
        );
    }

    /** ======================
     *  Helpers – تطبيع الأرقام
     *  ====================== */

    /**
     * الصيغة الداخلية القياسية: 00963xxxxxxxxx
     * (نسخة محلية داخل الـService)
     */
    private function normalizeCanonical00963Local(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (preg_match('/^00963\d{9}$/', $digits)) {
            return $digits; // جاهزة للتخزين
        }

        if (preg_match('/^09\d{8}$/', $digits)) {
            return '00963' . substr($digits, 1);
        }

        throw new \InvalidArgumentException('صيغة رقم غير مسموح بها.');
    }

    private function toSmsE164PlusFromCanonical(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        // من 00963xxxxxxxxx إلى +963xxxxxxxxx
        if (preg_match('/^00963\d{9}$/', $digits)) {
            return '+963' . substr($digits, 5);
        }

        // لو وصل 09xxxxxxxx نحوله مباشرة للإرسال
        if (preg_match('/^09\d{8}$/', $digits)) {
            return '+963' . substr($digits, 1);
        }

        // لو كان أصلاً بصيغة +963xxxxxxxxx
        if (preg_match('/^\+963\d{9}$/', $phone)) {
            return $phone;
        }

        throw new \InvalidArgumentException('صيغة رقم غير صالحة للإرسال.');
    }


    private function cacheKey(string $tempId): string
    {
        return 'register:pending:' . $tempId;
    }




}




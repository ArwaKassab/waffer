<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\CartRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CustomerAuthSafrjalService
{
    protected UserRepositoryInterface $userRepo;
    protected AddressService $addressService;
    protected RedisCartService $redisCartService;
    protected CartRepository $cartRepo;
    protected SafrjalOtpService $otpService;

    public function __construct(
        SafrjalOtpService $otpService,
        UserRepositoryInterface $userRepo,
        AddressService $addressService,
        RedisCartService $redisCartService,
        CartRepository $cartRepo
    ) {
        $this->userRepo = $userRepo;
        $this->addressService = $addressService;
        $this->redisCartService = $redisCartService;
        $this->cartRepo = $cartRepo;
        $this->otpService = $otpService;
    }

    /**
     * 1) بدء التسجيل: نخزّن البيانات مؤقتًا، نرسل OTP عبر Safrjal (WhatsApp)، ونرجع temp_id.
     */
    public function startRegistration(array $data, ?string $visitorId = null): array
    {
        $tempId = (string) Str::uuid();

        $data['phone'] = $this->normalizeCanonical00963Local($data['phone'] ?? '');

        $payload = $data;
        $payload['password'] = Hash::make($data['password']);
        $payload['type'] = 'customer';
        $payload['visitor_id'] = $visitorId;

        // توليد OTP
        $otpPlain = $this->generateOtp(6);
        $payload['otp_hash'] = Hash::make($otpPlain);

        // تخزين مؤقت
        $encrypted = Crypt::encryptString(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $ttlSeconds = (int) config('services.safrjal.expire', 300) + 120;
        Cache::put($this->cacheKey($tempId), $encrypted, $ttlSeconds);

        // Safrjal يريد رقم دولي بدون + (مثال: 9639xxxxxxxx)
        $phoneForSafrjal = $this->toSafrjalInternationalNoPlusFromCanonical($data['phone']);


            $sendMeta = $this->otpService->sendOtp(
                $phoneForSafrjal,
                $otpPlain,
                config('services.safrjal.title', 'wafir - وافر')
            );

        if (!empty($sendMeta['ok'])) {
            return [$tempId, ['ok' => true]];
        }

        Log::error('Safrjal OTP failed', ['meta' => $sendMeta, 'temp_id' => $tempId]);
        return [$tempId, ['ok' => false]];

    }

    public function finalizeRegistration(string $tempId, string $phone, string $otpInput): User
    {
        $encrypted = Cache::pull($this->cacheKey($tempId));
        if (!$encrypted) {
            throw new \RuntimeException('انتهت صلاحية جلسة التسجيل. أعد المحاولة.');
        }

        $json = Crypt::decryptString($encrypted);
        $data = json_decode($json, true) ?: [];

        $storedPhone = $this->normalizeCanonical00963Local($data['phone'] ?? '');
        $inputPhone  = $this->normalizeCanonical00963Local($phone);

        if ($storedPhone === '' || $storedPhone !== $inputPhone) {
            Cache::put($this->cacheKey($tempId), $encrypted, 120);
            throw new \RuntimeException('بيانات التسجيل غير متطابقة.');
        }

        if (empty($data['otp_hash']) || !Hash::check($otpInput, $data['otp_hash'])) {
            Cache::put($this->cacheKey($tempId), $encrypted, 120);
            throw new \RuntimeException('رمز التفعيل غير صحيح.');
        }

        DB::beginTransaction();
        try {
            $user = $this->userRepo->create([
                'name'     => $data['name'],
                'phone'    => $storedPhone,
                'password' => $data['password'],
                'type'     => $data['type'] ?? 'customer',
                'area_id'  => $data['area_id'],
            ]);

            $this->addressService->createAddressForUser($user->id, [
                'area_id'          => $data['area_id'],
                'address_details'  => $data['address_details'],
                'latitude'         => $data['latitude'] ?? null,
                'longitude'        => $data['longitude'] ?? null,
                'title'            => $data['title'],
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

        $json = Crypt::decryptString($encrypted);
        $data = json_decode($json, true) ?: [];

        $storedPhone = $this->normalizeCanonical00963Local($data['phone'] ?? '');
        $inputPhone  = $this->normalizeCanonical00963Local($phone);

        if ($storedPhone === '' || $storedPhone !== $inputPhone) {
            throw new \RuntimeException('رقم الهاتف لا يطابق الجلسة الحالية.');
        }

        // OTP جديد
        $otpPlain = $this->generateOtp(6);
        $data['otp_hash'] = Hash::make($otpPlain);

        // تجديد التخزين + TTL
        $encryptedNew = Crypt::encryptString(json_encode($data, JSON_UNESCAPED_UNICODE));
        $ttlSeconds = (int) config('services.safrjal.expire', 300) + 120;
        Cache::put($cacheKey, $encryptedNew, $ttlSeconds);

        $phoneForSafrjal = $this->toSafrjalInternationalNoPlusFromCanonical($storedPhone);

        $sendMeta = $this->otpService->sendOtp(
            $phoneForSafrjal,
            $otpPlain,
            config('services.safrjal.title', 'wafir - وافر')
        );

        return ['ok' => true, 'provider_response' => $sendMeta];
    }

    /** ======================
     * Helpers
     * ====================== */

    private function generateOtp(int $length = 6): string
    {
        $min = (int) ('1' . str_repeat('0', $length - 1));
        $max = (int) str_repeat('9', $length);
        return (string) random_int($min, $max);
    }

    private function normalizeCanonical00963Local(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (preg_match('/^00963\d{9}$/', $digits)) {
            return $digits;
        }

        if (preg_match('/^09\d{8}$/', $digits)) {
            return '00963' . substr($digits, 1);
        }

        throw new \InvalidArgumentException('صيغة رقم غير مسموح بها.');
    }

    /**
     * 00963xxxxxxxxx -> 963xxxxxxxxx (بدون +)
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

        // لو وصل أصلاً 9639xxxxxxxx
        if (preg_match('/^963\d{9}$/', $digits)) {
            return $digits;
        }

        throw new \InvalidArgumentException('صيغة رقم غير صالحة للإرسال.');
    }

    private function cacheKey(string $tempId): string
    {
        return 'register:pending:' . $tempId;
    }
}

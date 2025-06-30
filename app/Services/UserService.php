<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\CartRepository;
use App\Services\AddressService;
use App\Services\RedisCartService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class UserService
{
    protected UserRepositoryInterface $userRepo;
    protected AddressService $addressService;
    protected RedisCartService $redisCartService;
    protected CartRepository $cartRepo;

    public function __construct(UserRepositoryInterface $userRepo, AddressService $addressService, RedisCartService $redisCartService,CartRepository $cartRepo)
    {
        $this->userRepo = $userRepo;
        $this->addressService = $addressService;
        $this->redisCartService = $redisCartService;
        $this->cartRepo = $cartRepo;
    }


    public function sendResetPasswordCode(string $phone): array
    {
        $processedPhone = $this->userRepo->processPhoneNumber($phone);

        $user = $this->userRepo->findByPhoneAndType($phone, 'customer');
        if (!$user) {
            return ['success' => false, 'message' => 'رقم الهاتف غير مسجل','status' => 404];
        }

        $otp = rand(100000, 999999);
        Redis::setex('reset_password_code:' . $processedPhone, 600, $otp);

        // هنا ممكن تضيف ارسال الرسالة SMS لو عندك خدمة

        return ['success' => true, 'message' => 'تم إرسال رمز التحقق إلى رقم الهاتف صالح لمدة 10 دقائق','message2front' => 'الرمز يرسل بالريسبونس مؤقتا ليتم الاتفاق على الية ال OTP','otp' => $otp];
    }

    public function verifyResetPasswordCode(string $phone, string $otp): array
    {
        $processedPhone = $this->userRepo->processPhoneNumber($phone);
        $cachedOtp = Redis::get('reset_password_code:' . $processedPhone);

        if (!$cachedOtp || $cachedOtp !== $otp) {
            return ['success' => false, 'message' => 'رمز التحقق غير صحيح أو منتهي الصلاحية'];
        }

        $tempToken = Str::random(64);
        Redis::setex('reset_password_token:' . $processedPhone, 600, $tempToken);

        return ['success' => true, 'message' => 'رمز التحقق صحيح','message2front' => 'التوكن مؤقت صالح لمدة 10 دقائق الرجاء ارساله مع (API) في السكرين التالية ', 'temp_token' => $tempToken];
    }

    public function resetPassword(string $phone, string $newPassword, string $token): array
    {
        $processedPhone = $this->userRepo->processPhoneNumber($phone);
        $cachedToken = Redis::get('reset_password_token:' . $processedPhone);

        if (!$cachedToken || $cachedToken !== $token) {
            return ['success' => false, 'message' => 'رمز الدخول المؤقت غير صحيح أو منتهي'];
        }

        $user = $this->userRepo->findByPhoneAndType($phone, 'customer');
        if (!$user) {
            return ['success' => false, 'message' => 'رقم الهاتف غير مسجل'];
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        Redis::del('reset_password_token:' . $processedPhone);
        Redis::del('reset_password_code:' . $processedPhone);

        return ['success' => true, 'message' => 'تم تحديث كلمة المرور بنجاح'];
    }


}

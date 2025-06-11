<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\AddressService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class UserService
{
    protected UserRepositoryInterface $userRepo;
    protected AddressService $addressService;

    public function __construct(UserRepositoryInterface $userRepo, AddressService $addressService)
    {
        $this->userRepo = $userRepo;
        $this->addressService = $addressService;
    }
    /**
     * تسجيل مستخدم جديد من نوع عميل مع إنشاء العنوان المرتبط به
     *
     * @param array $data بيانات التسجيل (name, phone, password, area_id, address_details, latitude, longitude)
     * @return \App\Models\User
     * @throws Exception
     */
    public function registerCustomer(array $data)
    {
        DB::beginTransaction();

        try {
            $data['password'] = Hash::make($data['password']);
            $data['type'] = 'customer';

            $user = $this->userRepo->create($data);

            $this->addressService->createAddressForUser($user->id, [
                'area_id' => $data['area_id'],
                'address_details' => $data['address_details'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
            ]);
//            $this->migrateGuestCartToUser($request->session()->getId(), $user->id);

            DB::commit();

            return $user;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
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

//    public function migrateGuestCartToUser($sessionId, $userId)
//    {
//        $cartItems = Redis::get("guest_cart:{$sessionId}");
//
//        if ($cartItems) {
//            $items = json_decode($cartItems, true);
//
//            foreach ($items as $item) {
//                cartitem::create([
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

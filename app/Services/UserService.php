<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\CartRepository;
use App\Services\AddressService;
use App\Services\RedisCartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserService
{
    protected UserRepositoryInterface $userRepo;
    protected AddressService $addressService;
    protected RedisCartService $redisCartService;
    protected CartRepository $cartRepo;

    public function __construct(UserRepositoryInterface $userRepo, AddressService $addressService, RedisCartService $redisCartService, CartRepository $cartRepo)
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
            return ['success' => false, 'message' => 'رقم الهاتف غير مسجل', 'status' => 404];
        }

        $otp = rand(100000, 999999);
        Redis::setex('reset_password_code:' . $processedPhone, 600, $otp);

        return ['success' => true, 'message' => 'تم إرسال رمز التحقق إلى رقم الهاتف', 'message2front' => 'الرمز يرسل بالريسبونس مؤقتا', 'otp' => $otp];
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

        return [
            'success' => true,
            'message' => 'رمز التحقق صحيح',
            'message2front' => 'التوكن مؤقت صالح لمدة 10 دقائق',
            'temp_token' => $tempToken
        ];
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

    public function updateProfile(User $user, Request $request): array
    {
        $data = $request->only([
            'name', 'phone', 'email',
            'area_id', 'image', 'open_hour', 'close_hour','status',
            'note', 'current_password', 'new_password', 'new_password_confirmation',
        ]);

        if (!empty($data['new_password'])) {
            if (!isset($data['current_password']) || !Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages(['current_password' => 'كلمة المرور الحالية غير صحيحة']);
            }

            $user->password = Hash::make($data['new_password']);
        }
        if (!empty($data['image']) && $data['image'] instanceof UploadedFile) {
            $path = Storage::disk('public')->put('user_images', $data['image']);
            $data['image'] = $path;
        }

        unset($data['current_password'], $data['new_password'], $data['new_password_confirmation']);

        $user->fill($data);
        $user->save();

        return [
            'message' => 'تم تحديث الحساب بنجاح',
            'user' => array_merge(
                $user->toArray(),
                ['image' => $user->image ? asset('storage/' . $user->image) : null]
            ),
        ];
    }

    public function changeArea(User $user, int $new_area_id): array
    {
        $user->area_id = $new_area_id;
        $user->save();

        return [
            'message' => 'تم تحديث المنطقة بنجاح',
            'user' => array_merge(
                $user->toArray(),
                ['image' => $user->image ? asset('storage/' . $user->image) : null]
            ),
        ];
    }

    public function softDeleteAccount(User $user, int $graceDays = 30): void
    {
        DB::transaction(function () use ($user, $graceDays) {
            $user->tokens()->delete();
            $user->phone_shadow = $user->phone;
            $deadPhone = 'del_'.Str::lower(Str::random(12));
            $user->phone = $deadPhone;

            $user->status = false;
            $user->image = null;
            $user->email = null;



            $user->save();
            $user->delete();
        });
    }
}




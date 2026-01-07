<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Eloquent\CartRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class CustomerFirebaseAuthService
{
    public function __construct(
        private AddressService $addressService,
        private RedisCartService $redisCartService,
        private CartRepository $cartRepo
    ) {}

    public function register(array $data, string $firebaseUid, string $firebasePhoneE164, ?string $visitorId = null): User
    {
        $canonicalPhone = $this->toCanonical00963($firebasePhoneE164);

        if (User::where('phone', $canonicalPhone)->exists()) {
            throw new \RuntimeException('الرقم مستخدم مسبقًا.');
        }

        DB::beginTransaction();
        try {
            $createData = [
                'name'              => $data['name'],
                'phone'             => $canonicalPhone,
                'password'          => Hash::make($data['password']),
                'type'              => 'customer',
                'area_id'           => (int) $data['area_id'],
                'phone_verified_at' => now(),
            ];

            // إذا عندك firebase_uid في الجدول
            if (Schema::hasColumn('users', 'firebase_uid')){
                $createData['firebase_uid'] = $firebaseUid;
            }

            $user = User::create($createData);

            $this->addressService->createAddressForUser($user->id, [
                'area_id'          => (int) $data['area_id'],
                'address_details'  => $data['address_details'],
                'latitude'         => isset($data['latitude']) ? (float) $data['latitude'] : null,
                'longitude'        => isset($data['longitude']) ? (float) $data['longitude'] : null,
                'title'            => $data['title'],
            ]);

            $cart = $this->cartRepo->getCartByUserId($user->id);
            if (!$cart) {
                $this->cartRepo->createCartForUser($user->id);
            }

            if (!empty($visitorId)) {
                $this->redisCartService->migrateVisitorCartToUserCart($visitorId, $user->id);
            }

            DB::commit();
            return $user;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function resetPassword(string $firebasePhoneE164, string $newPassword): void
    {
        $canonicalPhone = $this->toCanonical00963($firebasePhoneE164);

        $user = User::where('phone', $canonicalPhone)->first();
        if (!$user) {
            throw new \RuntimeException('المستخدم غير موجود.');
        }

        $user->forceFill([
            'password' => Hash::make($newPassword),
        ])->save();

        // إبطال توكنات Sanctum القديمة (إن وجدت)
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }
    }

    private function toCanonical00963(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        // +9639xxxxxxxx -> 9639xxxxxxxx -> 009639xxxxxxxx
        if (preg_match('/^963\d{9}$/', $digits)) {
            return '00' . $digits;
        }

        if (preg_match('/^00963\d{9}$/', $digits)) {
            return $digits;
        }

        throw new \InvalidArgumentException('صيغة رقم Firebase غير مدعومة: ' . $phone);
    }
}

//
//namespace App\Services;
//
//use App\Models\User;
//use App\Repositories\Eloquent\CartRepository;
//use Illuminate\Support\Facades\DB;
//use Illuminate\Support\Facades\Hash;
//
//class CustomerFirebaseAuthService
//{
//    public function __construct(
//        private AddressService   $addressService,
//        private RedisCartService $redisCartService,
//        private CartRepository   $cartRepo
//    )
//    {
//    }
//
//    public function registerCustomer(array $data, string $firebaseUid, string $firebasePhoneE164, ?string $visitorId = null): User
//    {
//        $canonicalPhone = $this->toCanonical00963($firebasePhoneE164);
//
//        if (User::where('phone', $canonicalPhone)->exists()) {
//            throw new \RuntimeException('الرقم مستخدم مسبقًا.');
//        }
//
//        DB::beginTransaction();
//        try {
//            $user = User::create([
//                'name' => $data['name'],
//                'phone' => $canonicalPhone,
//                'password' => !empty($data['password']) ? Hash::make($data['password']) : null,
//                'type' => 'customer',
//                'area_id' => $data['area_id'],
//                'firebase_uid' => $firebaseUid,
//                'phone_verified_at' => now(),
//            ]);
//
//            $this->addressService->createAddressForUser($user->id, [
//                'area_id' => $data['area_id'],
//                'address_details' => $data['address_details'],
//                'latitude' => $data['latitude'] ?? null,
//                'longitude' => $data['longitude'] ?? null,
//                'title' => $data['title'],
//            ]);
//
//            $cart = $this->cartRepo->getCartByUserId($user->id);
//            if (!$cart) {
//                $this->cartRepo->createCartForUser($user->id);
//            }
//
//            if (!empty($visitorId)) {
//                $this->redisCartService->migrateVisitorCartToUserCart($visitorId, $user->id);
//            }
//
//            DB::commit();
//            return $user;
//
//        } catch (\Throwable $e) {
//            DB::rollBack();
//            throw $e;
//        }
//    }
//
//    public function resetPassword(string $firebasePhoneE164, string $newPassword): void
//    {
//        $canonicalPhone = $this->toCanonical00963($firebasePhoneE164);
//
//        $user = User::where('phone', $canonicalPhone)->first();
//        if (!$user) {
//            throw new \RuntimeException('المستخدم غير موجود.');
//        }
//
//        $user->forceFill([
//            'password' => Hash::make($newPassword),
//        ])->save();
//
//        // إبطال التوكنات القديمة (Sanctum)
//        if (method_exists($user, 'tokens')) {
//            $user->tokens()->delete();
//        }
//    }
//
//    /**
//     * Firebase phone عادة يكون E.164: +9639xxxxxxxx
//     * نُحوّله لصيغتك الداخلية: 009639xxxxxxxx
//     */
//    private function toCanonical00963(string $phone): string
//    {
//        $digits = preg_replace('/\D+/', '', $phone);
//
//        // +9639xxxxxxxx -> digits: 9639xxxxxxxx
//        if (preg_match('/^963\d{9}$/', $digits)) {
//            return '00' . $digits; // 00963...
//        }
//
//        // 00963xxxxxxxxx
//        if (preg_match('/^00963\d{9}$/', $digits)) {
//            return $digits;
//        }
//
//        // أحياناً تأتي 963 بدون +
//        if (preg_match('/^963\d{9}$/', $digits)) {
//            return '00' . $digits;
//        }
//
//        throw new \InvalidArgumentException('صيغة رقم Firebase غير مدعومة: ' . $phone);
//    }
//}

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

class CustomerAuthService
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

    /**
     * تسجيل مستخدم جديد من نوع عميل مع إنشاء العنوان المرتبط به
     *
     * @param array $data
     * @param string|null $visitorId معرف سلة الزائر (اختياري)
     * @return \App\Models\User
     * @throws Exception
     */
    public function registerCustomer(array $data, ?string $visitorId = null)
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

            // 2. إنشاء سلة للمستخدم (إذا لم تكن موجودة)
            $userCart = $this->cartRepo->getCartByUserId($user->id);
            if (!$userCart) {
                $userCart = $this->cartRepo->createCartForUser($user->id);
            }

            // 3. ترحيل سلة الزائر إن وجدت visitor_id
            if ($visitorId) {
                $this->redisCartService->migrateVisitorCartToUserCart($visitorId, $user->id);
            }


            DB::commit();

            return $user;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }



}

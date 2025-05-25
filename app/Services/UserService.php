<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\AddressRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Exception;

class UserService
{
    protected $userRepo;
    protected $addressService;

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
            // تجهيز بيانات المستخدم
            $data['password'] = Hash::make($data['password']);
            $data['type'] = 'customer';

            // إنشاء المستخدم
            $user = $this->userRepo->create($data);

            $this->addressService->createAddressForUser($user->id, [
                'area_id' => $data['area_id'],
                'address_details' => $data['address_details'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
            ]);


            DB::commit();

            return $user;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

<?php

namespace App\Services;

use App\Repositories\Contracts\AddressRepositoryInterface;
use Exception;

class AddressService
{
    protected $addressRepo;

    public function __construct(AddressRepositoryInterface $addressRepo)
    {
        $this->addressRepo = $addressRepo;
    }

    public function createAddressForUser(int $userId, array $addressData)
    {
        $address = [
            'user_id' => $userId,
            'area_id' => $addressData['area_id'],
            'address_details' => $addressData['address_details'],
            'latitude' => $addressData['latitude'],
            'longitude' => $addressData['longitude'],
            'is_default' => true,  // هنا تحدد القيمة
        ];

        try {
            return $this->addressRepo->create($address);

        } catch (Exception $e) {
            throw $e;
        }
    }
}

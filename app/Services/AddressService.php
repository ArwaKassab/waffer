<?php

namespace App\Services;

use App\Repositories\Contracts\AddressRepositoryInterface;
use Exception;
use App\Models\Address;
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
            'title' => $addressData['title'],
            'address_details' => $addressData['address_details'],
            'latitude' => $addressData['latitude'],
            'longitude' => $addressData['longitude'],
            'is_default' => true,
        ];

        try {
            return $this->addressRepo->create($address);

        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getAllForUser($userId)
    {
        return $this->addressRepo->getUserAddresses($userId);
    }

    public function add(array $data)
    {
        if ($data['is_default'] ?? false) {
            Address::where('user_id', $data['user_id'])->update(['is_default' => false]);
        }

        return $this->addressRepo->create($data);
    }

    public function update(Address $address, array $data)
    {
        if (isset($data['is_default']) && $data['is_default']) {
            Address::where('user_id', $address->user_id)->update(['is_default' => false]);
        }

        return $this->addressRepo->update($address, $data);
    }

    public function delete(Address $address)
    {
        $userId = $address->user_id;
        $all = $this->addressRepo->getUserAddresses($userId);

        if ($all->count() <= 1) {
            return ['success' => false, 'message' => 'لا يمكن حذف العنوان الوحيد.'];
        }

        $wasDefault = $address->is_default;
        $this->addressRepo->delete($address);

        if ($wasDefault) {
            $another = $this->addressRepo->getOtherAddress($userId, $address->id);
            if ($another) {
                $this->addressRepo->update($another, ['is_default' => true]);
            }
        }

        return ['success' => true, 'message' => 'تم حذف العنوان بنجاح'];
    }
    public function getByIdForUser(int $id, int $userId)
    {
        return $this->addressRepo->findByIdAndUser($id, $userId);
    }

}

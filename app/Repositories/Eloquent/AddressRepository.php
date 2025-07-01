<?php
namespace App\Repositories\Eloquent;

use App\Models\Address;
use App\Repositories\Contracts\AddressRepositoryInterface;

class AddressRepository implements AddressRepositoryInterface
{
    public function create(array $data)
    {
        return Address::create($data);
    }

    public function getUserAddresses(int $userId)
    {
        return Address::where('user_id', $userId)->get();
    }

    public function update(Address $address, array $data)
    {
        $address->update($data);
        return $address->fresh();
    }


    public function delete(Address $address)
    {
        return $address->delete();
    }

    public function getByIdAndUser(int $id, int $userId): ?Address
    {
        return Address::where('id', $id)->where('user_id', $userId)->first();
    }

    public function getDefaultAddress(int $userId): ?Address
    {
        return Address::where('user_id', $userId)->where('is_default', true)->first();
    }

    public function getOtherAddress(int $userId, int $excludeId)
    {
        return Address::where('user_id', $userId)->where('id', '!=', $excludeId)->first();
    }
    public function findByIdAndUser(int $id, int $userId)
    {
        return Address::where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

}

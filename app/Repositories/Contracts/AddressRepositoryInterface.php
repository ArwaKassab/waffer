<?php

namespace App\Repositories\Contracts;

use App\Models\Address;

interface AddressRepositoryInterface
{
    public function create(array $data);

    public function getUserAddresses(int $userId);

    public function update(Address $address, array $data);

    public function delete(Address $address);

    public function getByIdAndUser(int $id, int $userId): ?Address;

    public function getDefaultAddress(int $userId): ?Address;

    public function getOtherAddress(int $userId, int $excludeId);

    public function findByIdAndUser(int $id, int $userId); // مكرر مع getByIdAndUser (نقترح توحيد الاسم لاحقًا)
}

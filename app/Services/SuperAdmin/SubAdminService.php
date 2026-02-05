<?php

namespace App\Services\SuperAdmin;

use App\Repositories\Contracts\SubAdminRepositoryInterface;
use App\Models\User;

class SubAdminService
{
    public function __construct(SubAdminRepositoryInterface $addressRepo)
    {
        $this->repo = $addressRepo;
    }
    // إنشاء Sub Admin جديد مرتبط بمنطقة
    public function createSubAdmin(array $data): User
    {
        $data['type'] = 'sub_admin';
        $data['password'] = bcrypt($data['password']);

        return $this->repo->createSubAdmin($data);
    }

    // قائمة Sub Admins
    public function listSubAdmins()
    {
        return $this->repo->getSubAdmins();
    }
}

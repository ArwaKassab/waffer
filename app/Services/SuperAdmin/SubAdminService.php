<?php

namespace App\Services\SuperAdmin;

use App\Repositories\SubAdminRepository;
use App\Models\User;

class SubAdminService
{
    public function __construct(protected SubAdminRepository $repo) {}

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

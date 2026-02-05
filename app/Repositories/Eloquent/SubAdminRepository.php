<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use Illuminate\Support\Collection;

class SubAdminRepository
{
    // إنشاء Sub Admin
    public function createSubAdmin(array $data): User
    {
        return User::create($data);
    }

    // الحصول على كل Sub Admins (اختياري)
    public function getSubAdmins(): Collection
    {
        return User::where('type', 'sub_admin')->orderBy('name')->get();
    }
}

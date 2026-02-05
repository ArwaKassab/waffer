<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface SubAdminRepositoryInterface
{
    // إنشاء Sub Admin جديد
    public function create(array $data): User;

    // جلب كل Sub Admins
    public function all(): Collection;

    // جلب Sub Admin معين حسب ID
    public function find(int $id): ?User;
}

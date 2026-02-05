<?php


namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\SubAdminRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class SubAdminRepository implements SubAdminRepositoryInterface
{
    public function createSubAdmin(array $data): User
    {
        return User::create($data);
    }

    public function all(): Collection
    {
        return User::where('type', 'sub_admin')->get();
    }

    public function find(int $id): ?User
    {
        return User::where('type', 'sub_admin')->find($id);
    }
}


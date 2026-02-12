<?php

namespace App\Services\SuperAdmin;

use App\Repositories\Eloquent\AreaRepository;
use App\Models\Area;
use Illuminate\Support\Collection;

class AreaService
{
    public function __construct(protected AreaRepository $repo) {}

    // إنشاء منطقة جديدة
    public function create(array $data): Area
    {
        return $this->repo->create($data);
    }

    // قائمة جميع المناطق
    public function listAll(): Collection
    {
        return $this->repo->getAll();
    }

    public function delete(int $id): bool
    {
        $area = Area::find($id);
        if (!$area) return false;

        return $area->delete();
    }

    public function find(int $id)
    {
        return Area::find($id);
    }

    public function all()
    {
        return Area::all();
    }
}

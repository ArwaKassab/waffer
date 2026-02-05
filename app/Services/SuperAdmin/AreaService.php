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
}

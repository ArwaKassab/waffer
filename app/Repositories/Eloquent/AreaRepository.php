<?php

namespace App\Repositories\Eloquent;

use App\Models\Area;
use App\Repositories\Contracts\AreaRepositoryInterface;

class AreaRepository implements AreaRepositoryInterface
{
    public function getAll()
    {
        return Area::select('id', 'name')->get();
    }

    // إنشاء منطقة جديدة
    public function create(array $data): Area
    {
        return Area::create($data);
    }
}

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
}

<?php

namespace App\Services;

use App\Models\Area;
use App\Repositories\Contracts\AreaRepositoryInterface;

class AreaService
{
    protected AreaRepositoryInterface $areaRepository;

    public function __construct(AreaRepositoryInterface $areaRepository)
    {
        $this->areaRepository = $areaRepository;
    }

    public function getAllAreas()
    {
        return $this->areaRepository->getAll();
    }

    public function create(array $data): Area
    {
        return $this->areaRepository->create($data);
    }
}

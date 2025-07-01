<?php

namespace App\Services;

use App\Repositories\Contracts\ComplaintRepositoryInterface;

class ComplaintService
{
    protected $repository;

    public function __construct(ComplaintRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function addComplaint(array $data)
    {
        return $this->repository->create($data);
    }
}

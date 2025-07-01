<?php

namespace App\Repositories\Eloquent;

use App\Models\Complaint;
use App\Repositories\Contracts\ComplaintRepositoryInterface;

class ComplaintRepository implements ComplaintRepositoryInterface
{
    public function create(array $data)
    {
        return Complaint::create($data);
    }
}

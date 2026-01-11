<?php

namespace App\Repositories\Contracts;

use App\Models\Complaint;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ComplaintRepositoryInterface
{
    public function create(array $data): Complaint;

    public function paginateForAdmin(int $perPage = 20): LengthAwarePaginator;

    public function findForAdminById(int $id): ?Complaint;
}

<?php

// app/Repositories/UserRepositoryInterface.php
namespace App\Repositories\Contracts;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CustomerRepositoryInterface
{
    public function getCustomersByAreaIdPaginated(int $areaId, int $perPage): LengthAwarePaginator;
    public function baseQuery(int $areaId): Builder;

}

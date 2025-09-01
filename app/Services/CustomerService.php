<?php

// app/Services/SubAdminUserService.php
namespace App\Services;

use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerService
{
    public function __construct(private CustomerRepositoryInterface $customers) {}

    public function listCustomersForSubAdminAreaPaginated(int $areaId, int $perPage): LengthAwarePaginator
    {
        return $this->customers->getCustomersByAreaIdPaginated($areaId, $perPage);
    }

    public function searchByNamePrefix(int $areaId, string $prefix, int $perPage): LengthAwarePaginator
    {
        return $this->customers->searchCustomersByNamePrefix($areaId, $prefix, $perPage);
    }

    public function searchByPhonePrefix(int $areaId, string $prefix, int $perPage): LengthAwarePaginator
    {
        return $this->customers->searchCustomersByPhonePrefix($areaId, $prefix, $perPage);
    }
}

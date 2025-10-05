<?php

namespace App\Repositories\Contracts;

interface OrderRepositoryInterface
{
    public function create(array $data);

    public function addItems($orderId, array $items);

    public function getOrdersByUser($userId);

    public function addDiscounts($orderId, array $discounts);

    public function findById($id);

    public function update($id, array $data);

    public function updateStatus(int $orderId, string $newStatus): bool;
    public function allowedStatuses(): array;

}

<?php

namespace App\Repositories\Eloquent;

use App\Models\AreaHomeOrder;

class AreaHomeOrderRepository
{
    public function upsertOrder(int $areaId, string $type, array $ids): void
    {
        $rows = [];
        foreach (array_values($ids) as $i => $id) {
            $rows[] = [
                'area_id' => $areaId,
                'entity_type' => $type,
                'entity_id' => (int) $id,
                'sort_order' => $i + 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        AreaHomeOrder::upsert(
            $rows,
            ['area_id', 'entity_type', 'entity_id'],
            ['sort_order', 'is_active', 'updated_at']
        );
    }

    public function toggle(int $areaId, string $type, int $entityId, bool $isActive): void
    {
        AreaHomeOrder::updateOrCreate(
            ['area_id' => $areaId, 'entity_type' => $type, 'entity_id' => $entityId],
            ['is_active' => $isActive]
        );
    }

    public function nextSortOrder(int $areaId, string $type): int
    {
        $max = AreaHomeOrder::query()
            ->where('area_id', $areaId)
            ->where('entity_type', $type)
            ->max('sort_order');

        return ((int) $max) + 1;
    }

    public function addToEndIfMissing(int $areaId, string $type, int $entityId): void
    {
        AreaHomeOrder::query()->updateOrCreate(
            [
                'area_id' => $areaId,
                'entity_type' => $type,
                'entity_id' => $entityId,
            ],
            [
                'sort_order' => $this->nextSortOrder($areaId, $type),
                'is_active'  => true,
            ]
        );
    }

}

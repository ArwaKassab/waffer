<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class VisitorService
{
    public static function setArea(string $visitorId, int $areaId): void
    {
        Redis::set("visitor:{$visitorId}:area", $areaId);
        Redis::expire("visitor:{$visitorId}:area", 60 * 60 * 24 * 30); // 30 يوم
    }

    public static function getArea(string $visitorId): ?string
    {
        return Redis::get("visitor:{$visitorId}:area");
    }

    public static function clearArea(string $visitorId): void
    {
        Redis::del("visitor:{$visitorId}:area");
    }
}

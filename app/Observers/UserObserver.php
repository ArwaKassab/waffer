<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserObserver
{
    public function updated(User $user): void
    {
        // فقط إذا تغير رصيد المحفظة
        if (! $user->wasChanged('wallet_balance')) {
            return;
        }

        // إذا الزبون فقط (اختياري لكن منطقي)
        if ($user->type !== 'customer') {
            return;
        }

        $areaId = $user->area_id;

        // مفاتيح المنطقة
        if ($areaId) {
            Cache::forget("area:{$areaId}:customers_wallet_total");
            Cache::forget("area:{$areaId}:customers_wallet_with_balance_count");
        }

        // إذا عندك مفاتيح عامة (بدون منطقة) امسحيها كمان
        Cache::forget("customers_wallet_total");
        Cache::forget("customers_wallet_with_balance_count");
    }
}

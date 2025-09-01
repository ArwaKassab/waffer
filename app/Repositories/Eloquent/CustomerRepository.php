<?php

// app/Repositories/EloquentUserRepository.php
namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\CustomerRepositoryInterface;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRepository implements CustomerRepositoryInterface
{
    /** القاعدة المشتركة لكل استعلامات العملاء ضمن منطقة معيّنة */
    public function baseQuery(int $areaId): Builder
    {
        return User::query()
            ->where('area_id', $areaId)
            ->whereRaw('LOWER(type) = ?', ['customer'])
            ->with(['addresses'])
            ->withCount('orders')
            ->select(['id','name','phone','wallet_balance','is_banned','area_id','type']);
    }

    public function getCustomersByAreaIdPaginated(int $areaId, int $perPage): LengthAwarePaginator
    {
        return $this->baseQuery($areaId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /** بحث بالاسم: بداية الكلمة (q% أو % q%) وحد أدنى حرفين */
    public function searchCustomersByNamePrefix(int $areaId, string $prefix, int $perPage): LengthAwarePaginator
    {
        $q = trim($prefix);
        if (mb_strlen($q) < 2) {
            // ترجيع قائمة فارغة بنفس شكل الـ paginator
            return $this->baseQuery($areaId)->whereRaw('1=0')->paginate($perPage);
        }

        return $this->baseQuery($areaId)
            ->where(function ($w) use ($q) {
                $w->where('name', 'like', $q.'%')       // بداية السطر
                ->orWhere('name', 'like', '% '.$q.'%'); // بداية كلمة داخل الاسم
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    /** بحث بالهاتف: يطابق البداية، ويحوّل 0xxxx إلى 00963xxxx تلقائيًا */
    public function searchCustomersByPhonePrefix(int $areaId, string $prefix, int $perPage): LengthAwarePaginator
    {
        $q = trim($prefix);

        if (mb_strlen($q) < 2) {
            return $this->baseQuery($areaId)->whereRaw('1=0')->paginate($perPage);
        }


        if (str_starts_with($q, '09')) {

            $q = '009639' . substr($q, 2);
        }

        elseif (str_starts_with($q, '0')) {
            $q = '009639' . substr($q, 1);
        }

        return $this->baseQuery($areaId)
            ->where('phone', 'like', $q.'%')
            ->orderBy('phone')
            ->paginate($perPage);
    }
}

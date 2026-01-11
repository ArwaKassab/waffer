<?php

namespace App\Repositories\Eloquent;

use App\Models\Complaint;
use App\Repositories\Contracts\ComplaintRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ComplaintRepository implements ComplaintRepositoryInterface
{
    public function create(array $data): Complaint
    {
        /** @var Complaint $complaint */
        $complaint = Complaint::create($data);

        return $complaint;
    }

    public function paginateForAdmin(int $perPage = 20): LengthAwarePaginator
    {
        return Complaint::query()
            ->select(['id', 'user_id', 'type', 'created_at'])
            ->with(['user:id,name,phone'])
            ->latest('id')
            ->paginate($perPage);
    }

    public function findForAdminById(int $id): ?Complaint
    {
        $row = Complaint::query()
            ->select(['id', 'user_id', 'type', 'message', 'created_at'])
            ->with(['user:id,name,phone'])
            ->whereKey($id)
            ->first();

        // لإرضاء الـ IDE + حماية إضافية
        return $row instanceof Complaint ? $row : null;
    }
}

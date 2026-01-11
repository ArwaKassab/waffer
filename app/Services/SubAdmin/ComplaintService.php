<?php

namespace App\Services\SubAdmin;

use App\Models\Complaint;
use App\Repositories\Contracts\ComplaintRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ComplaintService
{
    public function __construct(
        protected ComplaintRepositoryInterface $complaints
    ) {}

    public function listAll(int $perPage = 20): LengthAwarePaginator
    {
        return $this->complaints->paginateForAdmin($perPage);
    }

    public function findById(int $id): ?Complaint
    {
        return $this->complaints->findForAdminById($id);
    }

    /**
     * توحيد شكل الإخراج للواجهة
     */
    public function payload(Complaint $c, bool $withMessage = false): array
    {
        $base = [
            'id' => $c->id,
            'user' => [
                'id'    => $c->user?->id,
                'name'  => $c->user?->name,
                'phone' => $c->user?->phone,
            ],
            'type' => $c->type,
            'date' => optional($c->created_at)->toDateString(),     // YYYY-MM-DD
            'time' => optional($c->created_at)->format('H:i:s'),    // HH:MM:SS
            'created_at' => optional($c->created_at)->toDateTimeString(),
        ];

        if ($withMessage) {
            $base['message'] = $c->message;
        }

        return $base;
    }
}

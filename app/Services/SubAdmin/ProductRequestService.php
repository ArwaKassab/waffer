<?php

namespace App\Services\SubAdmin;

use App\Repositories\Eloquent\ProductRequestRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ProductRequestService
{
    protected ProductRequestRepository $repository;

    public function __construct(ProductRequestRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getCreateRequestDetails(int $requestId ,int $area_id): array
    {
        $admin = Auth::user();

        $request = $this->repository
            ->findCreateRequestForAdmin($requestId, $area_id);

        if (! $request) {
            throw ValidationException::withMessages([
                'request' => 'الطلب غير موجود أو لا ينتمي لمنطقتك.'
            ]);
        }

        return [
            'request_id' => $request->id,
            'status'     => $request->status,
            'review_note'=> $request->review_note,
            'created_at' => $request->created_at,

            'store' => [
                'id'    => $request->store->id,
                'name'  => $request->store->name,
                'phone' => $request->store->phone_display,
            ],

            'product' => [
                'name'        => $request->name,
                'details'     => $request->details,
                'price'       => $request->price,
                'quantity'    => $request->quantity,
                'unit'        => $request->unit,
                'status'      => $request->status_value,
                'image_url'   => $request->image_url,
            ],
        ];
    }

    public function getAllCreateRequestsForAdmin(): array
    {
        $admin = auth()->user();

        if (! $admin) {
            abort(401);
        }

        $requests = $this->repository
            ->getCreateRequestsForAdminArea($admin->area_id);

        return $requests->map(function ($request) {

            return [
                'request_id' => $request->id,
                'status'     => $request->status,
                'created_at' => $request->created_at,

                'product' => [
                    'name'      => $request->name,
                    'image_url' => $request->image_url,
                ],

                'store' => [
                    'id'   => $request->store->id,
                    'name' => $request->store->name,
                ],
            ];

        })->toArray();
    }




}

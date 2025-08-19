<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Models\ProductRequest;
use Illuminate\Database\Eloquent\Collection;

class ProductRequestRepository
{
    public function hasPendingForProduct(int $productId): bool
    {
        return ProductRequest::where('product_id', $productId)
            ->where('action', 'update')
            ->where('status', 'pending')
            ->exists();
    }

    public function hasPendingCreateForStore(int $storeId, string $name): bool
    {
        return ProductRequest::where('action','create')
            ->where('status','pending')
            ->where('store_id', $storeId)
            ->where('name', $name) // منع ازدواجية نفس الاسم أثناء الانتظار
            ->exists();
    }


    public function hasPendingDeleteForProduct(int $productId): bool
    {
        return ProductRequest::where('product_id', $productId)
            ->where('action', 'delete')
            ->where('status', 'pending')
            ->exists();
    }


    public function getPendingByProduct(int $productId): ?ProductRequest
    {
        return ProductRequest::where('product_id', $productId)
            ->where('action', 'update')
            ->where('status', 'pending')
            ->first();
    }

    public function createUpdateRequest(Product $product, array $data, int $storeId): ProductRequest
    {
        return ProductRequest::create([
            'action'      => 'update',
            'product_id'  => $product->id,
            'store_id'    => $storeId,
            'status'      => 'pending',
            'name'        => $data['name']     ?? null,
            'price'       => $data['price']    ?? null,
            'status_value'=> $data['status']   ?? null,
            'quantity'    => $data['quantity'] ?? null,
            'unit'        => $data['unit']     ?? null,
            'image'        => $data['image']     ?? null,
            'product_updated_at_snapshot' => $product->updated_at,
        ]);
    }

    public function createCreateRequest(int $storeId, array $data, ): ProductRequest
    {
        return ProductRequest::create([
            'action'      => 'create',
            'store_id'    => $storeId,
            'status'      => 'pending',
            'name'        => $data['name'],
            'price'       => $data['price'],
            'status_value'=> $data['status'],
            'quantity'    => $data['quantity'],
            'image'        => $data['image']     ?? null,
            'unit'        => $data['unit'],
        ]);
    }

    public function createDeleteRequest(Product $product, int $storeId): ProductRequest
    {
        return ProductRequest::create([
            'action' => 'delete',
            'product_id' => $product->id,
            'store_id' => $storeId,
            'status' => 'pending',
            'product_updated_at_snapshot' => $product->updated_at, // لقفل تفاؤلي
        ]);
    }

    public function markApproved(ProductRequest $req,  ?string $note = null): void
    {
        $req->update([
            'status' => 'approved',
            'review_note' => $note,
        ]);
    }

    public function markRejected(ProductRequest $req, ?string $note = null): void
    {
        $req->update([
            'status' => 'rejected',
            'review_note' => $note,
        ]);
    }
}

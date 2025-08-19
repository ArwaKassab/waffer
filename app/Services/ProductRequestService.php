<?php

// app/Services/ProductRequestService.php
namespace App\Services;

use App\Models\Product;
use App\Models\ProductRequest;
use App\Repositories\Eloquent\ProductRequestRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductRequestService
{
    public function __construct(
        private ProductRequestRepository $repo
    ){}

    /**
     * إنشاء طلب تعديل واحد فقط للمنتج.
     */
    public function submitUpdateRequest(Product $product, array $data, int $storeId): ProductRequest
    {
        if ($this->repo->hasPendingForProduct($product->id)) {
            throw ValidationException::withMessages([
                'request' => 'يوجد طلب تعديل معلّق لهذا المنتج بالفعل.',
            ]);
        }

        return $this->repo->createUpdateRequest($product, $data, $storeId);
    }
    public function submitCreateRequest( array $data, int $storeId): ProductRequest
    {

        if ($this->repo->hasPendingCreateForStore($storeId, $data['name'])) {
            throw ValidationException::withMessages(['request' => 'يوجد طلب إنشاء معلق لنفس الاسم في هذا المتجر.']);
        }
        return $this->repo->createCreateRequest($storeId, $data);
    }


    public function submitDeleteRequest(Product $product, int $storeId): ProductRequest
    {
        if ($this->repo->hasPendingDeleteForProduct($product->id)) {
            throw ValidationException::withMessages([
                'request' => 'يوجد طلب حذف معلّق لهذا المنتج بالفعل.',
            ]);
        }
        return $this->repo->createDeleteRequest($product, $storeId);
    }

    /**
     * موافقة الأدمن وتطبيق التعديلات بأمان (قفل تفاؤلي + ترانزاكشن).
     */
    public function approve(ProductRequest $req, ?string $note = null, ?int $storeId = null): Product
    {
        if ($req->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'الطلب ليس بحالة Pending.']);
        }

        return DB::transaction(function () use ($req, $note, $storeId) {

            if ($req->action === 'update') {
                $product = $req->product()->lockForUpdate()->firstOrFail();

                if ($req->product_updated_at_snapshot && $product->updated_at->ne($req->product_updated_at_snapshot)) {
                    throw ValidationException::withMessages(['concurrency' => 'تم تعديل المنتج بعد إنشاء الطلب.']);
                }

                $changes = array_filter([
                    'name'     => $req->name,
                    'price'    => $req->price,
                    'status'   => $req->status_value,
                    'quantity' => $req->quantity,
                    'unit'     => $req->unit,
                    'image'    => $req->image,
                ], fn($v) => !is_null($v));

                if ($changes) {
                    $product->fill($changes)->save();
                }

                $this->repo->markApproved($req, $note);
                return $product;
            }

            if ($req->action === 'create') {
                if (!$storeId) {
                    throw ValidationException::withMessages(['store' => 'store_id مطلوب عند الموافقة على الإنشاء.']);
                }

                $product = new Product([
                    'store_id' => $storeId,
                    'name'     => $req->name,
                    'price'    => $req->price,
                    'status'   => $req->status_value ?? 'available',
                    'quantity' => $req->quantity ?? 0,
                    'unit'     => $req->unit,
                    'image'    => $req->image,
                ]);
                $product->save();

                $req->product_id = $product->id;
                $this->repo->markApproved($req, $note);
                return $product;
            }

            if ($req->action === 'delete') {
                $product = $req->product()->lockForUpdate()->firstOrFail();

                if ($req->product_updated_at_snapshot && $product->updated_at->ne($req->product_updated_at_snapshot)) {
                    throw ValidationException::withMessages(['concurrency' => 'تم تعديل المنتج بعد إنشاء الطلب.']);
                }

                // Soft delete (يدعم الاسترجاع لو لزم)
                $product->delete();

                $this->repo->markApproved($req, $note);
                return $product;
            }

            throw ValidationException::withMessages(['action' => 'نوع الطلب غير مدعوم.']);
        });
    }

    public function reject(ProductRequest $req, int $adminId, ?string $note=null): void
    {
        if ($req->status === 'pending') {
            $this->repo->markRejected($req, $adminId, $note);
        }
    }
}

<?php

namespace App\Services;

use App\Events\StoreProductChanged;
use App\Models\Product;
use App\Models\ProductRequest;
use App\Repositories\Eloquent\ProductRequestRepository;
use Illuminate\Database\Eloquent\Collection;
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


    public function editPendingRequest(int $requestId, int $storeId, array $data): ProductRequest
    {
        $req = $this->repo->findPendingByIdForStore($requestId, $storeId);
        if (!$req) {
            throw ValidationException::withMessages(['request' => 'لا يوجد طلب معلّق بهذا المعرّف.']);
        }

        if (array_key_exists('status', $data)) {
            $data['status_value'] = $data['status'];
            unset($data['status']);
        }

        if ($req->action === 'create') {
            if (isset($data['name']) && $this->repo->existsOtherPendingCreateWithName($storeId, $data['name'], $req->id)) {
                throw ValidationException::withMessages(['name' => 'يوجد طلب إضافة معلق بنفس الاسم في هذا المتجر.']);
            }
            return $this->repo->updateRequestFields($req, $data);
        }

        if ($req->action === 'update') {
            $product = $req->product()->first();
            if (!$product) {
                throw ValidationException::withMessages(['product' => 'المنتج المرتبط بالطلب غير موجود.']);
            }
            if ((int)$product->store_id !== (int)$storeId) {
                throw ValidationException::withMessages(['store' => 'هذا الطلب لا يتبع متجرك.']);
            }
            $req->product_updated_at_snapshot = $product->updated_at;
            $req->save();

            return $this->repo->updateRequestFields($req, $data);
        }

        throw ValidationException::withMessages(['action' => 'نوع الطلب غير مدعوم.']);
    }


    public function getPendingRequests(int $storeId): Collection
    {
        return $this->repo->getPendingRequestsForStore($storeId);
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
                    'details' => $req->details,
                ], fn($v) => !is_null($v));

                if ($changes) {
                    $product->fill($changes)->save();
                }

                $this->repo->markApproved($req, $note);
                event(new \App\Events\ProductRequestReviewed($req, true, $note));

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
                    'details'  => $req->details,
                ]);
                $product->save();

                $req->product_id = $product->id;
                $this->repo->markApproved($req, $note);
                event(new \App\Events\ProductRequestReviewed($req, true, $note));

                return $product;
            }

            if ($req->action === 'delete') {
                $product = $req->product()->lockForUpdate()->firstOrFail();

                if ($req->product_updated_at_snapshot && $product->updated_at->ne($req->product_updated_at_snapshot)) {
                    throw ValidationException::withMessages(['concurrency' => 'تم تعديل المنتج بعد إنشاء الطلب.']);
                }

                $product->delete();

                $this->repo->markApproved($req, $note);
                event(new \App\Events\ProductRequestReviewed($req, true, $note));
                return $product;
            }

            throw ValidationException::withMessages(['action' => 'نوع الطلب غير مدعوم.']);
        });
    }

    public function reject(ProductRequest $req, ?string $note=null): void
    {
        if ($req->status === 'pending') {
            $this->repo->markRejected($req, $note);
            event(new \App\Events\ProductRequestReviewed($req, false, $note));
        }
    }

    public function cancelCreateRequest(int $requestId, int $storeId): void
    {
        $req = $this->repo->findPendingByIdForStore($requestId, $storeId);

        if (!$req) {
            throw ValidationException::withMessages([
                'request' => 'لا يوجد طلب إضافة معلّق بهذا المعرف يخص متجرك.'
            ]);
        }
        $this->repo->deleteRequest($req);
    }

    /**
     * تعديل منتج مباشرة بدون طلب موافقة.
     */
    public function directUpdateProduct(Product $product, array $data): Product
    {
        $updated = $this->repo->updateProductDirect($product, $data);

        $updated->loadMissing('store');

        if ($updated->store) {
            event(new StoreProductChanged(
                product: $updated,
                store: $updated->store,
                action: 'direct_update',
            ));
        }

        return $updated;
    }

    public function directDeleteProduct(Product $product): void
    {
        $product->loadMissing('store');
        $store = $product->store;

        $productId = $product->id;

        $this->repo->deleteProductDirect($product);

        if ($store) {
            $product->id = $productId;

            event(new StoreProductChanged(
                product: $product,
                store: $store,
                action: 'direct_delete',
            ));
        }
    }

}

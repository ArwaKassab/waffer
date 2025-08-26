<?php
// app/Repositories/Contracts/ProductChangeRequestRepositoryInterface.php

namespace App\Repositories\Contracts;

use App\Models\Product;
use App\Models\ProductRequest;

interface ProductRequestRepositoryInterface
{
    /**
     * هل يوجد طلب تعديل معلّق لهذا المنتج؟
     */
    public function hasPendingForProduct(int $productId): bool;

    /**
     * إرجاع الطلب المعلّق لمنتج معيّن (إن وجد).
     */
    public function getPendingByProduct(int $productId): ?ProductRequest;

    /**
     * إنشاء طلب تعديل (Update) لمنتج موجود.
     */
    public function createUpdateRequest(Product $product, array $data, int $userId): ProductRequest;

    /**
     * هل يوجد طلب إنشاء معلّق لنفس الاسم داخل متجر معيّن؟
     * (لمنع ازدواجية الطلبات أثناء الانتظار)
     */
    public function hasPendingCreateForStore(int $storeId, string $name): bool;

    /**
     * إنشاء طلب إنشاء (Create) لمنتج جديد داخل متجر.
     */
    public function createCreateRequest(int $storeId, array $data, int $userId): ProductRequest;

    /**
     * تعليم الطلب كمقبول (ويُفضّل استدعاؤها بعد تطبيق التغييرات في Service).
     */
    public function markApproved(ProductRequest $req, int $adminId, ?string $note = null): void;

    /**
     * تعليم الطلب كمرفوض.
     */
    public function markRejected(ProductRequest $req, int $adminId, ?string $note = null): void;

    public function updateCreateRequest(ProductRequest $req, array $data): ProductRequest;

}

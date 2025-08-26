<?php

// app/Services/DiscountService.php
namespace App\Services;

use App\Models\Product;
use App\Repositories\Eloquent\DiscountRepository;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class DiscountService
{
    public function __construct(private DiscountRepository $repo) {}

    public function addByStore(int $storeId, int $productId, array $data)
    {
        /** @var Product $product */
        $product = Product::findOrFail($productId);

        // تحقق ملكية المنتج
        if ((int)$product->store_id !== (int)$storeId) {
            throw ValidationException::withMessages(['product' => 'هذا المنتج لا يتبع متجرك.']);
        }


        $newPrice = (float)$data['new_price'];
        if ($newPrice >= (float)$product->price) {
            throw ValidationException::withMessages(['new_price' => 'السعر بعد الخصم يجب أن يكون أقل من السعر الأصلي.']);
        }

        // التواريخ
        $start = Carbon::parse($data['start_date']);
        $end   = Carbon::parse($data['end_date']);
        // لا يوجد تداخل مع خصومات active|scheduled
        if ($this->repo->hasOverlapping($product->id, $start, $end)) {
            throw ValidationException::withMessages(['date_range' => 'هناك خصم آخر يتقاطع مع هذه الفترة.']);
        }

        // تحديد الحالة
        $now = now();
        $status = ($start->lte($now) && $end->gte($now)) ? 'active' : 'scheduled';

        // إنشاء الخصم
        $discount = $this->repo->create($product->id, $newPrice, $start, $end, $status );

        return [$product, $discount];
    }
}

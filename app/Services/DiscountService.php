<?php

// app/Services/DiscountService.php
namespace App\Services;

use App\Models\Discount;
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
        if ($this->repo->hasOverlapping($product->id, $start, $end)) {
            throw ValidationException::withMessages(['date_range' => 'هناك خصم آخر يتقاطع مع هذه الفترة.']);
        }

        $discount = $this->repo->create($product->id, $newPrice, $start, $end );

        $product->loadMissing('store');

        if ($product->store) {
            event(new \App\Events\StoreProductDiscountAdded(
                product: $product,
                store: $product->store,
                discount: $discount,
            ));
        }
        return [$product, $discount];
    }

    /**
     * يلغي الخصم النشط لمنتج معيّن يخص متجر معيّن.
     *
     * @return array [Product $product, Discount $discount]
     */
    public function removeByStore(int $storeId, int $productId): array
    {

        /** @var Product $product */
        $product = Product::where('id', $productId)
            ->where('store_id', $storeId)
            ->first();

        if (!$product) {
            throw ValidationException::withMessages([
                'product' => 'هذا المنتج غير موجود أو لا يتبع متجرك.',
            ]);
        }

        /** @var Discount|null $discount */
        $discount = $product->discounts()
        ->where('status', 'active')
            ->first();

        if (!$discount) {
            throw ValidationException::withMessages([
                'discount' => 'لا يوجد خصم نشط لهذا المنتج.',
            ]);
        }
        $discount->delete();

        return [$product, $discount];
    }

}

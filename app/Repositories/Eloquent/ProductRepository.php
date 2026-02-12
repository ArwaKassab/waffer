<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class ProductRepository
{
    public function getProductById(int $id): ?Product
    {
        /** @var Product $product */
        $product = Product::with('store')->find($id);
        return $product;
    }

    public function getActiveDiscount($product, $quantity)
    {
        $activeDiscount = $product->activeDiscountToday();

        if ($activeDiscount) {
            $discountValue = ($product->price - $activeDiscount->new_price) * $quantity;

            return [
                'discount_value' => $discountValue,
                'order_discount_data' => [
                    'product_id' => $product->id,
                    'discount_id' => $activeDiscount->id,
                    'value' => $discountValue,
                ],
            ];
        }

        return null;
    }
    public function getStoreProducts(int $storeId, int $perPage = 10): LengthAwarePaginator
    {
        $products = Product::with('activeDiscount')
            ->where('store_id', $storeId)
            ->orderByRaw("CASE WHEN status = 'available' THEN 0 WHEN status = 'not_available' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->paginate($perPage);


        return $products;
    }

    /**
     * إرجاع قائمة الوحدات المتاحة للمنتجات.
     */
    public function getUnits(): array
    {
        return array_map(fn($u) => ['value' => $u], Product::UNITS);
    }

    public function create(array $data): Product
    {
        $product = Product::create([
            'name'     => $data['name'],
            'price'    => $data['price'],
            'image'    => $data['image'] ,
            'status'   => $data['status'] ?? 'available',
            'quantity' => $data['quantity'],
            'unit'     => $data['unit'],
            'details'  => $data['details'] ?? null,
            'store_id' => $data['store_id'],
        ]);

        return $product->fresh();
    }


    public function findInAdminArea(int $productId, int $areaId): ?Product
    {
        return Product::where('id', $productId)
            ->whereHas('store', function ($q) use ($areaId) {
                $q->where('area_id', $areaId);
            })
            ->first();
    }

    public function update(Product $product, array $data): bool
    {
        if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
            $data['image'] = $data['image']->store('products', 'public');
        }

        return $product->update($data);
    }


    public function delete(Product $product): bool
    {
        return $product->delete();
    }

}


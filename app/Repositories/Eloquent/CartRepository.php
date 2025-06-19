<?php

namespace App\Repositories\Eloquent;

use App\Models\Cart;
use App\Models\CartItem;
use App\Repositories\Contracts\CartRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CartRepository implements CartRepositoryInterface
{
    public function getCartByUserId($userId)
    {
        return Cart::with('items.product')->where('user_id', $userId)->first();
    }


    public function createCartForUser($userId)
    {
        return Cart::create(['user_id' => $userId]);
    }

    public function addItem($cartId, $productId, $quantity): void
    {
        $item = CartItem::where('cart_id', $cartId)
            ->where('product_id', $productId)
            ->first();

        if ($item) {
            if ($item->quantity !== $quantity) {
                $item->quantity = $quantity;
                $item->save();
            }
        } else {
            CartItem::create([
                'cart_id'    => $cartId,
                'product_id' => $productId,
                'quantity'   => $quantity,
            ]);
        }
    }


    public function updateItemQuantity($cartId, $productId, $quantity): void
    {
        $item = CartItem::where('cart_id', $cartId)->where('product_id', $productId)->first();

        if ($item) {
            $item->quantity = $quantity;
            $item->save();
        }
    }

    public function removeItem($cartId, $productId): void
    {
        CartItem::where('cart_id', $cartId)->where('product_id', $productId)->delete();
    }

    public function addItemsBulk(int $cartId, array $items): void
    {
        $data = [];
        foreach ($items as $productId => $quantity) {
            $data[] = [
                'cart_id' => $cartId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('cart_items')->insert($data);
    }

}

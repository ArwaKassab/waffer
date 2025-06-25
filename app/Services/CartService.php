<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\Eloquent\CartRepository;
use App\Services\RedisCartService;
use Illuminate\Support\Facades\DB;

class CartService
{
    protected CartRepository $cartRepo;
    protected RedisCartService $redisCart;

    public function __construct(CartRepository $cartRepo, RedisCartService $redisCart)
    {
        $this->cartRepo = $cartRepo;
        $this->redisCart = $redisCart;
    }

    public function getCart(?int $userId = null, ?string $visitorId = null)
    {
        if (!$userId && !$visitorId) {
            throw new \InvalidArgumentException("User ID or Visitor ID is required.");
        }

        if ($userId) {
            $cart = $this->cartRepo->getCartByUserId($userId);

            if (!$cart) {
                return [
                    'items' => [],
                    'total' => 0
                ];
            }

            $items = [];
            $total = 0;

            foreach ($cart->items as $item) {
                $lineTotal = $item->quantity * $item->product->price;
                $items[] = [
                    'product_id' => $item->product->id,
                    'name'       => $item->product->name,
                    'price'      => $item->product->price,
                    'quantity'   => $item->quantity,
                    'total'      => $lineTotal,
                ];
                $total += $lineTotal;
            }

            return [
                'items' => $items,
                'total' => $total
            ];
        }

        // إذا المستخدم زائر، نستخدم Redis
        $cartData = $this->redisCart->getCart($visitorId);
        $items = [];
        $total = 0;

        foreach ($cartData as $productId => $quantity) {
            $product = Product::find($productId);
            if ($product) {
                $lineTotal = $product->price * $quantity;
                $items[] = [
                    'product_id' => $product->id,
                    'name'       => $product->name,
                    'price'      => $product->price,
                    'quantity'   => $quantity,
                    'total'      => $lineTotal,
                ];
                $total += $lineTotal;
            }
        }

        return [
            'items' => $items,
            'total' => $total
        ];
    }


    public function addItem(int $productId, int $quantity, ?int $userId = null, ?string $visitorId = null): void
    {
        if (!$userId && !$visitorId) {
            throw new \InvalidArgumentException("User ID or Visitor ID is required.");
        }

        if ($userId) {
            DB::transaction(function () use ($userId, $productId, $quantity) {
                $cart = $this->cartRepo->getCartByUserId($userId) ;
                $this->cartRepo->addItem($cart->id, $productId, $quantity);
            });
            return;
        }

        $this->redisCart->addOrUpdateItem($visitorId, $productId, $quantity);
    }

    public function updateItem(int $productId, int $quantity, ?int $userId = null, ?string $visitorId = null): void
    {
        if (!$userId && !$visitorId) {
            throw new \InvalidArgumentException("User ID or Visitor ID is required.");
        }

        if ($userId) {
            DB::transaction(function () use ($userId, $productId, $quantity) {
                $cart = $this->cartRepo->getCartByUserId($userId);
                if (!$cart) {
                    throw new \Exception("Cart not found for user ID {$userId}.");
                }
                $this->cartRepo->updateItemQuantity($cart->id, $productId, $quantity);
            });
            return;
        }

        $this->redisCart->updateItem($visitorId, $productId, $quantity);
    }

    public function removeItem(int $productId, ?int $userId = null, ?string $visitorId = null): void
    {
        if (!$userId && !$visitorId) {
            throw new \InvalidArgumentException("User ID or Visitor ID is required.");
        }

        if ($userId) {
            DB::transaction(function () use ($userId, $productId) {
                $cart = $this->cartRepo->getCartByUserId($userId);
                if (!$cart) {
                    throw new \Exception("Cart not found for user ID {$userId}.");
                }
                $this->cartRepo->removeItem($cart->id, $productId);
            });
            return;
        }

        $this->redisCart->removeItem($visitorId, $productId);
    }
}

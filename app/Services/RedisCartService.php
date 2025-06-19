<?php

namespace App\Services;

use App\Repositories\Eloquent\CartRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class RedisCartService
{
    protected CartRepository $cartRepo;

    public function __construct(CartRepository $cartRepo)
    {
        $this->cartRepo = $cartRepo;
    }

    public function addOrUpdateItem(string $visitorId, int $productId, int $quantity): void
    {
        Redis::hset("visitor_cart:{$visitorId}", $productId, $quantity);
    }

    public function removeItem(string $visitorId, int $productId): void
    {
        Redis::hdel("visitor_cart:{$visitorId}", $productId);
    }

    public function updateItem(string $visitorId, int $productId, int $quantity): void
    {
        // لتحديث الكمية فقط إذا العنصر موجود
        if (Redis::hexists("visitor_cart:{$visitorId}", $productId)) {
            Redis::hset("visitor_cart:{$visitorId}", $productId, $quantity);
        }
    }

    public function getCart(string $visitorId): array
    {
        $cart = Redis::hgetall("visitor_cart:{$visitorId}");
        return array_map('intval', $cart);
    }

    public function clearCart(string $visitorId): void
    {
        Redis::del("visitor_cart:{$visitorId}");
    }



    public function migrateVisitorCartToUserCart(string $visitorId, int $userId): void
    {
        $visitorCart = $this->getCart($visitorId);

        if (empty($visitorCart)) {
            return;
        }

        $userCart = $this->cartRepo->getCartByUserId($userId) ?? $this->cartRepo->createCartForUser($userId);

        $this->cartRepo->addItemsBulk($userCart->id, $visitorCart);

        $this->clearCart($visitorId);
    }


}

<?php
namespace App\Events;

use App\Models\Product;
use App\Models\User;
use App\Models\Discount;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StoreProductDiscountAdded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Product $product,
        public User $store,
        public Discount $discount,
    ) {}
}

<?php

namespace App\Events;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StoreProductChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Product $product,
        public User $store,
        public string $action // 'direct_update' or 'direct_delete'
    ) {}
}

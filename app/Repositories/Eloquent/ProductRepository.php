<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Carbon\Carbon;

class ProductRepository implements ProductRepositoryInterface
{
    public function getProductById($id)
    {
        return Product::find($id);
    }
}

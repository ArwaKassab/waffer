<?php

namespace App\Events;
namespace App\Events;

use App\Models\ProductRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductRequestReviewed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ProductRequest $request,
        public bool $approved,
        public ?string $note
    ) {}
}

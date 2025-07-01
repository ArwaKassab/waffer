<?php

namespace App\Repositories\Eloquent;

use App\Models\Link;
use App\Repositories\Contracts\LinkRepositoryInterface;

class LinkRepository implements LinkRepositoryInterface
{
    public function all()
    {
        return Link::all();
    }
}

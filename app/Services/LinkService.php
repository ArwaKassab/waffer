<?php

namespace App\Services;

use App\Repositories\Contracts\LinkRepositoryInterface;

class LinkService
{
    protected $linkRepo;

    public function __construct(LinkRepositoryInterface $linkRepo)
    {
        $this->linkRepo = $linkRepo;
    }

    public function getAllLinks()
    {
        return $this->linkRepo->all();
    }
}


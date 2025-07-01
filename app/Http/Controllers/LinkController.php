<?php

namespace App\Http\Controllers;

use App\Services\LinkService;

class LinkController extends Controller
{
    protected $service;

    public function __construct(LinkService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $links = $this->service->getAllLinks();
        return response()->json($links);
    }
}


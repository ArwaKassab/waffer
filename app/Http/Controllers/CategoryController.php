<?php

namespace App\Http\Controllers;

use App\Services\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index(Request $request)
    {
        if (!$request->area_id) {
            return response()->json([
                'message' => 'المنطقة مطلوبة'
            ], 422);
        }

        $categories = $this->categoryService
            ->getCategoriesByArea($request->area_id);

        return response()->json([
            'data' => $categories
        ]);
    }

}


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
        $data = $request->validate([
            'area_id' => ['required','integer','exists:areas,id'],
        ]);
        $categories = $this->categoryService->getCategoriesByArea((int)$data['area_id']);

        return response()->json([
            'data' => $categories
        ]);
    }

}


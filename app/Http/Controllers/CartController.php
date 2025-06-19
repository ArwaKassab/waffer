<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CartService;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index(Request $request)
    {
        try {
            $cart = $this->cartService->getCart(auth('sanctum')->id(), $request->cookie('visitor_id'));
            return response()->json($cart);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function add(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $this->cartService->addItem(
                $validated['product_id'],
                $validated['quantity'],
                auth('sanctum')->id(),
                $request->cookie('visitor_id')
            );
            return response()->json(['message' => 'تمت الإضافة بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $this->cartService->updateItem(
                $validated['product_id'],
                $validated['quantity'],
                auth('sanctum')->id(),
                $request->cookie('visitor_id')
            );
            return response()->json(['message' => 'تم التعديل بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function remove(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        try {
            $this->cartService->removeItem(
                $validated['product_id'],
                auth('sanctum')->id(),
                $request->cookie('visitor_id')
            );
            return response()->json(['message' => 'تم الحذف بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

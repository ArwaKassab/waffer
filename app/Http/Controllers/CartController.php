<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\CartService;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
            'quantity'   => 'required|integer|min:1',
        ]);

        try {
            $this->cartService->addItem(
                $validated['product_id'],
                $validated['quantity'],
                auth('sanctum')->id(),
                $request->cookie('visitor_id')
            );

            return response()->json(['message' => 'تمت الإضافة بنجاح']);
        } catch (HttpExceptionInterface $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update($productId,Request $request)
    {
        if (!Product::find($productId)) {
            return response()->json(['error' => 'المنتج غير موجود'], 404);
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $this->cartService->updateItem(
                $productId,
                $validated['quantity'],
                auth('sanctum')->id(),
                $request->cookie('visitor_id')
            );
            return response()->json(['message' => 'تم التعديل بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function remove($productId, Request $request)
    {
        try {

            if (!Product::find($productId)) {
                return response()->json(['error' => 'المنتج غير موجود'], 404);
            }

            $this->cartService->removeItem(
                $productId,
                auth('sanctum')->id(),
                $request->cookie('visitor_id')
            );

            return response()->json(['message' => 'تم الحذف بنجاح']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}

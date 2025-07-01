<?php

namespace App\Http\Controllers;

use App\Services\WalletService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function balance(Request $request)
    {
        $user = $request->user();

        $balance = $this->walletService->getBalance($user);

        return response()->json([
            'wallet_balance' => $balance,
        ]);
    }
}

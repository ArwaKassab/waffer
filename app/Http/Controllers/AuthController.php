<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'type' => ['required', Rule::in(['admin', 'sub_admin', 'store', 'customer'])]
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'type' => $request->type,
            ]);

            // لو عندك خطوات إضافية هنا (logs - send mail - assign role)

            DB::commit();

            return response()->json([
                'message' => 'User registered successfully',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // admin login
    public function adminLogin(Request $request)
    {
        return $this->login($request, 'admin', 'admin-api');
    }

    // store login
    public function storeLogin(Request $request)
    {
        return $this->login($request, 'store', 'store-api');
    }

    // customer login
    public function customerLogin(Request $request)
    {
        return $this->login($request, 'customer', 'customer-api');
    }

    // general login handler
    private function login(Request $request, $expectedType, $guardName)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if ($user->type !== $expectedType) {
            return response()->json(['message' => 'Unauthorized for this section'], 403);
        }

        $token = $user->createToken($guardName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }

    // logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }


}

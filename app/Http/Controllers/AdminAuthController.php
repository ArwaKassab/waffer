<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminAuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'type'     => 'required|string|in:admin',
        ]);

        if ($request->type !== 'admin') {
            return response()->json([
                'message' => 'Invalid type for this register endpoint',
            ], 400);
        }
        DB::beginTransaction();

        try {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'type'     => 'admin',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Admin registered successfully',
                'user'    => $user,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Something went wrong while registering admin',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'name'    => 'required|string',
            'password' => 'required|string',
        ]);
        $user = User::where('name', $request->name)->where('type', 'admin')->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('admin-token', ['admin'])->plainTextToken;

        return response()->json(['token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Resources\CustomerResource;
use App\Models\User;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerAuthController extends Controller
{
    public function register(Request $request)
    {
        $request->merge([
            'phone' => '00963' . substr($request->phone, 1)
        ]);
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'phone'             => 'required|unique:users',
            'password'          => 'required|string|min:6|confirmed',
            'area_id'           => 'required|exists:areas,id',
            'address_details'   => 'required|string',
            'latitude'          => 'required|numeric',
            'longitude'         => 'required|numeric',
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name'     => $validated['name'],
                'phone'    => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'type'     => 'customer',
                'area_id'  => $validated['area_id'],
            ]);

            Address::create([
                'user_id'         => $user->id,
                'area_id'         => $validated['area_id'],
                'address_details' => $validated['address_details'],
                'latitude'        => $validated['latitude'],
                'longitude'       => $validated['longitude'],
                'is_default'      => true,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'تم تسجيل العميل بنجاح',
                'user'    => new CustomerResource($user),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'حدث خطأ أثناء تسجيل العميل',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'phone'    => 'required|regex:/^0\d{9}$/',
            'password' => 'required|string',
        ], [
            'phone.regex' => 'يجب أن يبدأ رقم الهاتف بـ 0 ويتكون من 10 خانات.',
        ]);

        $processedPhone = '00963' . substr($validated['phone'], 1);

        $user = User::where('phone', $processedPhone)
            ->where('type', 'customer')
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 401);
        }

        $token = $user->createToken('customer-token', ['customer'])->plainTextToken;

        return response()->json(['token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
    }
}

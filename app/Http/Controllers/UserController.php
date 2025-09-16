<?php

namespace App\Http\Controllers;


use App\Models\Address;
use App\Models\User;
use App\Services\AddressService;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\UserService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    protected $userService ;
    protected $userRepo;
    public function __construct(UserService $userService , UserRepositoryInterface $userRepo)
    {
        $this->userRepo = $userRepo;
        $this->userService = $userService;
    }

    public function profile()
    {
        $user = auth('sanctum')->user();

        if (!$user instanceof User) {
            return response()->json(['message' => 'المستخدم غير مصرح له'], 403);
        }

        $userData = $user->toArray();

        if ($user->type === 'store') {
            $filteredUser = [
                'name' => $userData['name'],
                'open_hour' => $userData['open_hour'],
                'close_hour' => $userData['close_hour'],
                'status' => $userData['status'],
                'image' => $userData['image'] ? asset('storage/' . $userData['image']) : null,
            ];
        } elseif ($user->type === 'customer') {
            $filteredUser = [
                'id' => $userData['id'],
                'name' => $userData['name'],
                'phone' => $userData['phone'],
                'area_id' => $userData['area_id'],

            ];
        } else {
            $filteredUser = ['message' => 'نوع المستخدم غير معروف'];
        }

        return response()->json(['user' => $filteredUser]);
    }



    public function updateProfile(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user instanceof User) {
            return response()->json(['message' => 'المستخدم غير مصرح له'], 403);
        }

        if ($request->has('phone') && preg_match('/^0\d{9}$/', $request->phone)) {
            $request->merge(['phone' => '00963' . substr($request->phone, 1)]);
        }

        if ($request->has('whatsapp_phone') && preg_match('/^0\d{9}$/', $request->whatsapp_phone)) {
            $request->merge(['whatsapp_phone' => '00963' . substr($request->whatsapp_phone, 1)]);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|unique:users,phone,' . $user->id,
            'whatsapp_phone' => 'sometimes|nullable|string',
            'email' => 'sometimes|nullable|email|unique:users,email,' . $user->id,
            'area_id' => 'sometimes|nullable|exists:areas,id',
            'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'open_hour' => 'sometimes|nullable|date_format:H:i:s',
            'close_hour' => 'sometimes|nullable|date_format:H:i:s',
            'note' => 'sometimes|nullable|string',
            'current_password' => 'required_with:new_password|string',
            'new_password' => 'nullable|string|min:6|confirmed:new_password_confirmation',

        ]);

        if ($request->has('phone') && preg_match('/^0\d{9}$/', $request->phone)) {
            $request->merge(['phone' => '00963' . substr($request->phone, 1)]);
        }


        if ($validator->fails()) {
            return response()->json([
                'message' => 'البيانات غير صالحة',
                'errors' => $validator->errors(),
            ], 422);
        }

        $response = $this->userService->updateProfile($user, $request);

        $userData = $response['user'];

        if ($user->type === 'store') {
            $filteredUser = [
                'name' => $userData['name'],
                'open_hour' => $userData['open_hour'],
                'close_hour' => $userData['close_hour'],
                'status' => $userData['status'],
                'image' => $userData['image'],
            ];
        } elseif($user->type === 'customer') {
            $filteredUser = [
                'name' => $userData['name'],
            ];
        }


        return response()->json([
            'message' => $response['message'],
            'user' => $filteredUser,
        ]);

    }
    public function changeArea(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user instanceof User) {
            return response()->json(['message' => 'المستخدم غير مصرح له'], 403);
        }

        $request->validate([
            'new_area_id' => 'required|exists:areas,id',
        ], [
            'new_area_id.required' => 'المنطقة مطلوبة.',
            'new_area_id.exists' => 'المنطقة المحددة غير موجودة.',
        ]);

        $response = $this->userService->changeArea($user, $request->new_area_id);

        return response()->json([
            'message' => $response['message'],
            'user' => $response['user'],
        ]);
    }



}

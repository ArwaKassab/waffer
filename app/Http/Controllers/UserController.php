<?php

namespace App\Http\Controllers;


use App\Models\Address;
use App\Models\DeviceToken;
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

        if (!$user instanceof \App\Models\User) {
            return response()->json(['message' => 'المستخدم غير مصرح له'], 403);
        }

        $user->loadMissing([
            'area' => fn ($q) => $q->withTrashed()->select('id','name')
        ]);
;

        if ($user->type === 'store') {
            $filteredUser = [
                'name'       => $user->name,
                'open_hour'  => $user->open_hour,
                'close_hour' => $user->close_hour,
                'status'     => $user->status,
                'image'      => $user->image ? asset('storage/'.$user->image) : null,
                'area_id'    => $user->area_id,
                'area_name'  => optional($user->area)->name,
            ];
        } elseif ($user->type === 'customer') {
            $filteredUser = [
                'id'        => $user->id,
                'name'      => $user->name,
                'phone'     => $user->phone,
                'area_id'   => $user->area_id,
                'area_name' => optional($user->area)->name,
            ];
        }

        return response()->json(['user' => $filteredUser]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user instanceof User) {
            return response()->json(['message' => 'المستخدم غير مصرح له'], 403);
        }

        // تطبيع أرقام الهاتف السورية: 0XXXXXXXXX -> 00963XXXXXXXXX
        if ($request->has('phone') && preg_match('/^0\d{9}$/', $request->phone)) {
            $request->merge(['phone' => '00963' . substr($request->phone, 1)]);
        }
        if ($request->has('whatsapp_phone') && preg_match('/^0\d{9}$/', $request->whatsapp_phone)) {
            $request->merge(['whatsapp_phone' => '00963' . substr($request->whatsapp_phone, 1)]);
        }

        $validator = Validator::make($request->all(), [
            'name'            => 'sometimes|string|max:255',
            'phone'           => 'sometimes|string|unique:users,phone,' . $user->id,
            'whatsapp_phone'  => 'sometimes|nullable|string',
            'email'           => 'sometimes|nullable|email|unique:users,email,' . $user->id,
            'area_id'         => 'sometimes|nullable|exists:areas,id',
            'image'           => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'open_hour'       => 'sometimes|nullable|date_format:H:i:s',
            'close_hour'      => 'sometimes|nullable|date_format:H:i:s',
            'note'            => 'sometimes|nullable|string',
            'current_password' => 'required_with:new_password|string',
            'new_password'     => 'nullable|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'البيانات غير صالحة',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $response = $this->userService->updateProfile($user, $request);
        $userData = $response['user'] ?? [];

        if ($request->filled('new_password')) {
            $user->tokens()->delete();

            // إن أردتِ حذف جميع التوكنات ما عدا الحالي (أبقي المستخدم الحالي فقط مسجّلًا):
            // $currentTokenId = optional($request->user()->currentAccessToken())->id;
            // $user->tokens()->when($currentTokenId, fn($q) => $q->where('id', '!=', $currentTokenId))->delete();

            // (اختياري) لو عندك جلسات ويب (guard:web) وتبغي تسجيل الخروج من كل الجلسات الأخرى:
            // \Auth::logoutOtherDevices($request->input('current_password'));
            DeviceToken::where('user_id', $user->id)->delete();

        }

        // تحضير بيانات الإرجاع حسب نوع المستخدم
        if ($user->type === 'store') {
            $filteredUser = [
                'name'       => $userData['name']       ?? $user->name,
                'open_hour'  => $userData['open_hour']  ?? $user->open_hour,
                'close_hour' => $userData['close_hour'] ?? $user->close_hour,
                'status'     => $userData['status']     ?? $user->status,
                'image'      => $userData['image']      ?? ($user->image_url ?? $user->image),
            ];
        } elseif ($user->type === 'customer') {
            $filteredUser = [
                'name' => $userData['name'] ?? $user->name,
            ];
        } else {
            $filteredUser = [
                'name' => $userData['name'] ?? $user->name,
            ];
        }
        $message = $response['message'] ?? 'تم تحديث الملف الشخصي بنجاح.';
        if ($request->filled('new_password')) {
            $message = 'تم تحديث كلمة المرور وتم تسجيل خروجك من جميع الأجهزة.';
        }

        return response()->json([
            'message' => $message,
            'user'    => $filteredUser,
        ], 200);
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

    public function deleteMyAccount(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'غير مصرح'], 401);
        }

        if ($user->type !== 'customer') {
            return response()->json(['message' => 'مسموح فقط لحسابات العملاء'], 403);
        }

        // التحقق من كلمة المرور الحالية
        $request->validate([
            'current_password' => 'required|string',
        ]);

        if (!Hash::check($request->input('current_password'), $user->password)) {
            return response()->json([
                'message' => 'البيانات غير صالحة.',
                'errors'  => [
                    'current_password' => ['كلمة المرور غير صحيحة.'],
                ],
            ], 422);
        }

        app(UserService::class)->softDeleteAccount($user);

        return response()->json([
            'message' => 'تم حذف حسابك بشكل آمن (Soft Delete). يمكنك التواصل مع فريق الدعم إن رغبت باسترجاعه.',
        ]);
    }




}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeviceToken;
use Illuminate\Validation\Rule;

class DeviceController extends Controller
{
    // POST /api/device-tokens
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'device_token' => 'required|string|max:512',
            'app_key'      => ['required', 'string', Rule::in(['customer', 'store'])],
            'package_name' => 'nullable|string|max:150',

            'device_type'  => 'nullable|string|max:50',
            'app_version'  => 'nullable|string|max:50',
        ]);

        $token = $data['device_token'];

        $row = DeviceToken::where('token', $token)->first();

        if ($row) {
            // إذا نفس التوكن مرتبط بمستخدم آخر → نعتبره “مكرر/منسوخ” وننقله للمستخدم الحالي
            if ((int)$row->user_id !== (int)$user->id) {
                $row->delete();
                $row = null;
            } else {
                // نفس المستخدم → تحديث
                $row->update([
                    'app_key'      => $data['app_key'],
                    'package_name' => $data['package_name'] ?? $row->package_name,
                    'device_type'  => $data['device_type'] ?? $row->device_type,
                    'app_version'  => $data['app_version'] ?? $row->app_version,
                    'last_used_at' => now(),
                ]);

                return response()->json(['success' => true, 'token_id' => $row->id]);
            }
        }

        $row = DeviceToken::create([
            'user_id'      => $user->id,
            'token'        => $token,
            'app_key'      => $data['app_key'],
            'package_name' => $data['package_name'] ?? null,
            'device_type'  => $data['device_type'] ?? null,
            'app_version'  => $data['app_version'] ?? null,
            'last_used_at' => now(),
        ]);

        return response()->json(['success' => true, 'token_id' => $row->id]);
    }
}

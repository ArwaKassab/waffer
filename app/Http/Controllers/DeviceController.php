<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // 1) Normalize token (هذا أهم شيء لمنع \n والفراغات)
        $token = $this->normalizeToken($data['device_token']);

        if ($token === '') {
            return response()->json([
                'success' => false,
                'message' => 'device_token غير صالح',
            ], 422);
        }

        $row = DB::transaction(function () use ($user, $data, $token) {
            // 2) اقفل سجل التوكن إن وجد لمنع السباق (race)
            $existing = DeviceToken::where('token', $token)->lockForUpdate()->first();

            // 3) في حال كان عندك بيانات قديمة فيها تكرار لنفس التوكن (نادر مع unique)
            //    هذا يحذف أي نسخ إضافية (احترازي فقط)
            DeviceToken::where('token', $token)
                ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
                ->delete();

            $payload = [
                'user_id'      => (int) $user->id,
                'token'        => $token,
                'app_key'      => $data['app_key'],
                'package_name' => $data['package_name'] ?? null,
                'device_type'  => $data['device_type'] ?? null,
                'app_version'  => $data['app_version'] ?? null,
                'last_used_at' => now(),
            ];

            if ($existing) {
                // 4) لو التوكن كان مرتبط بمستخدم ثاني: نعيد ربطه بالمستخدم الحالي
                //    (أفضل من delete/create لأن ذلك يقلل مشاكل التزامن)
                $existing->fill($payload)->save();
                return $existing->fresh();
            }

            // 5) غير موجود: أنشئ سجل جديد
            return DeviceToken::create($payload);
        });

        return response()->json([
            'success'  => true,
            'token_id' => $row->id,
        ]);
    }

    private function normalizeToken(string $token): string
    {
        $token = preg_replace('/\s+/', '', $token);
        return trim((string) $token);
    }
}

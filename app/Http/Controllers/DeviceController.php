<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeviceToken;

class DeviceController extends Controller
{
    // POST /api/device-tokens
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'device_token' => 'required|string|max:500',
            'device_type' => 'nullable|string|max:50',
            'app_version' => 'nullable|string|max:50',
        ]);

        $row = DeviceToken::updateOrCreate(
            ['token' => $data['device_token']],
            [
                'user_id' => $user->id,
                'device_type' => $data['device_type'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'token_id' => $row->id,
        ]);
    }
}

//
//namespace App\Http\Controllers;
//
//use App\Models\DeviceToken;
//use Illuminate\Http\Request;
//
//class DeviceController extends Controller
//{
//    // POST /api/device-tokens
//    public function store(Request $request)
//    {
//        $user = $request->user(); // sanctum user
//
//        $data = $request->validate([
//            'device_token' => 'required|string|max:500',
//            'device_type'  => 'nullable|string|max:50',
//            'app_version'  => 'nullable|string|max:50',
//        ]);
//
//        // خزِّن أو حدّث
//        $row = DeviceToken::updateOrCreate(
//            ['token'   => $data['device_token']], // unique by token
//            [
//                'user_id'     => $user->id,
//                'device_type' => $data['device_type'] ?? null,
//                'app_version' => $data['app_version'] ?? null,
//                'last_used_at'=> now(),
//            ]
//        );
//
//        return response()->json([
//            'success' => true,
//            'token_id' => $row->id,
//        ]);
//    }
//}

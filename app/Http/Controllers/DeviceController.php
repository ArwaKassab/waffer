<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function register(Request $r){
        $r->validate([
            'token'       => 'required|string',
            'platform'    => 'nullable|in:android,ios,web',
            'device_name' => 'nullable|string|max:255',
        ]);

        $user = $r->user();

        DeviceToken::updateOrCreate(
            ['token' => $r->token],
            [
                'user_id'     => $user->id,
                'platform'    => $r->platform,
                'device_name' => $r->device_name,
                'last_used_at'=> now(),
            ]
        );

        return response()->json(['message' => 'Token saved']);
    }

    public function unregister(Request $r){
        $r->validate(['token'=>'required|string']);
        DeviceToken::where('token',$r->token)->delete();
        return response()->json(['message' => 'Token removed']);
    }
}

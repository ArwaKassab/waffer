<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/firebase-config.js', function () {
    $cfg = [
        'config' => [
            'apiKey'            => config('services.firebase_web.api_key'),
            'authDomain'        => config('services.firebase_web.auth_domain'),
            'projectId'         => config('services.firebase_web.project_id'),
            'messagingSenderId' => config('services.firebase_web.sender_id'),
            'appId'             => config('services.firebase_web.app_id'),
        ],
        'vapidPublicKey' => config('services.firebase_web.vapid_public_key'),
    ];

    return response('self._FIREBASE = '.json_encode($cfg, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).';')
        ->header('Content-Type','application/javascript')
        ->header('Cache-Control','no-store, no-cache, must-revalidate');
});


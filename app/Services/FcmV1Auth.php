<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;

class FcmV1Auth
{
    public function getAccessToken(): string
    {
        return Cache::remember('fcm_v1_access_token', now()->addMinutes(50), function () {
            $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

            // نقرأ من config/services.php
            $file = (string) config('services.fcm_v1.service_account_file');
            $json = config('services.fcm_v1.service_account_json');

            if ($json) {
                $arr = json_decode($json, true);
                if (!is_array($arr) || empty($arr['client_email'])) {
                    throw new \RuntimeException('FCM: service_account_json غير صالح.');
                }
                $creds = new ServiceAccountCredentials($scopes, $arr);
            } elseif ($file && is_file($file) && is_readable($file)) {
                $creds = new ServiceAccountCredentials($scopes, $file);
            } else {
                throw new \RuntimeException('FCM: لم يتم ضبط بيانات الخدمة (لا JSON ولا FILE).');
            }

            // ✅ الطريقة الصحيحة لجلب التوكن
            $token = $creds->fetchAuthToken();
            if (empty($token['access_token'])) {
                throw new \RuntimeException('FCM: فشل جلب access_token.');
            }
            return $token['access_token'];
        });
    }
}



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

            $file = config('services.fcm_v1.service_account_file');
            $json = config('services.fcm_v1.service_account_json');

            if ($json) {
                $creds = new ServiceAccountCredentials($scopes, json_decode($json, true));
            } else {
                $creds = new ServiceAccountCredentials($scopes, $file);
            }

            $token = $creds->fetchAuthToken();
            if (empty($token['access_token'])) {
                throw new \RuntimeException('Failed to fetch FCM v1 access token.');
            }
            return $token['access_token'];
        });
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FcmV1Client
{
    public function __construct(protected FcmV1Auth $auth) {}

    protected function endpoint(): string
    {
        $projectId = config('services.fcm_v1.project_id');
        return "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        $accessToken = $this->auth->getAccessToken();

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'data' => array_map('strval', $data), // FCM data لازم كلها strings
                'android' => ['priority' => 'HIGH'],
                'apns'    => ['headers' => ['apns-priority' => '10']],
            ],
        ];

        Http::withToken($accessToken)
            ->acceptJson()
            ->post($this->endpoint(), $payload)
            ->throw();
    }
}

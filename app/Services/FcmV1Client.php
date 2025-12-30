<?php

namespace App\Services;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmV1Client
{
    public function __construct(protected FcmV1Auth $auth) {}

    protected function endpoint(): string
    {
        $projectId = (string) config('services.fcm_v1.project_id');
        if (! $projectId) {
            throw new \RuntimeException('FCM project_id is not set.');
        }

        return "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        $response = Http::withToken($this->auth->getAccessToken())
            ->acceptJson()
            ->timeout(15)
            ->retry(2, 300)
            ->post($this->endpoint(), [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title ?: 'إشعار جديد',
                        'body'  => $body ?: '',
                    ],
                    'data' => array_map('strval', $data),
                ],
            ]);

        $http = $response->status();
        $json = $response->json() ?? [];

        Log::info('FCM response', [
            'token'  => $token,
            'http'   => $http,
            'body'   => $json,
        ]);

        if (! $response->failed()) {
            return;
        }

        $error   = $json['error'] ?? [];
        $status  = $error['status'] ?? null;   // مثل: NOT_FOUND / UNAUTHENTICATED
        $message = $error['message'] ?? null;  // مثل: NotRegistered

        // Extract FCM errorCode from details (إن وجد)
        $detailCode = null;
        foreach (($error['details'] ?? []) as $d) {
            if (is_array($d) && isset($d['errorCode'])) {
                $detailCode = $d['errorCode'];
                break;
            }
        }

        Log::error('FCM error parsed', [
            'token'       => $token,
            'http'        => $http,
            'status'      => $status,
            'detail_code' => $detailCode,
            'message'     => $message,
        ]);

        // ---------------------------
        // ✅ Invalid / expired token
        // ---------------------------
        $invalidCodes = ['UNREGISTERED', 'INVALID_ARGUMENT', 'NOT_FOUND'];

        $isInvalidToken =
            in_array($detailCode, $invalidCodes, true) ||
            in_array($status, $invalidCodes, true) ||
            in_array($message, ['NotRegistered'], true) ||                 // مهم لحالتك
            ($http === 404 && in_array($status, ['NOT_FOUND'], true));     // 404 غالبًا توكن/كيان غير موجود

        if ($isInvalidToken) {
            DeviceToken::where('token', $token)->delete();

            Log::warning('Deleted invalid FCM token', [
                'token'       => $token,
                'http'        => $http,
                'status'      => $status,
                'detail_code' => $detailCode,
                'message'     => $message,
            ]);

            return; // لا نرمي exception حتى لا يفشل الـ Job
        }

        // ---------------------------
        // 🟥 Auth / Permission errors
        // ---------------------------
        if (in_array($http, [401, 403], true) || in_array($status, ['UNAUTHENTICATED', 'PERMISSION_DENIED'], true)) {
            throw new \RuntimeException('FCM authentication/permission failed.');
        }

        // ---------------------------
        // 🔁 Other errors (retryable)
        // ---------------------------
        $response->throw();
    }
}

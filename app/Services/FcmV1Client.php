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
        if (!$projectId) {
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
                    'token'        => $token,
                    'notification' => [
                        'title' => $title ?: 'إشعار جديد',
                        'body'  => $body ?: '',
                    ],
                    'data' => array_map('strval', $data),
                ],
            ]);

        if (! $response->failed()) {
            Log::info('FCM success', [
                'token'  => $token,
                'status' => $response->status(),
            ]);
            return;
        }

        $json   = $response->json() ?? [];
        $error  = $json['error'] ?? [];
        $status = $error['status'] ?? null;
        $msg    = $error['message'] ?? null;

        $detailCode = null;
        $details = $error['details'] ?? [];
        if (is_array($details)) {
            foreach ($details as $item) {
                if (isset($item['errorCode'])) {
                    $detailCode = $item['errorCode'];
                    break;
                }
            }
        }

        Log::error('FCM error', [
            'token'       => $token,
            'http_status' => $response->status(),
            'status'      => $status,
            'detail_code' => $detailCode,
            'message'     => $msg,
            'raw'         => $json,
        ]);

        // حالات توكن غير صالح (واضحة)
        $invalidCodes = ['UNREGISTERED', 'INVALID_ARGUMENT'];
        $isInvalidToken = in_array($detailCode, $invalidCodes, true) || in_array($status, $invalidCodes, true);

        if ($isInvalidToken) {
            DeviceToken::where('token', $token)->delete();
            Log::warning('Deleted invalid FCM token', [
                'token'       => $token,
                'status'      => $status,
                'detail_code' => $detailCode,
            ]);
            return;
        }

        // Auth / Credentials
        if (in_array($response->status(), [401, 403], true) || $status === 'UNAUTHENTICATED') {
            throw new \RuntimeException('FCM authentication failed.');
        }

        // باقي الأخطاء → نخلي الـ queue يعيد المحاولة
        $response->throw();
    }
}

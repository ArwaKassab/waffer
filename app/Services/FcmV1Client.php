<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\DeviceToken;

class FcmV1Client
{
    public function __construct(protected FcmV1Auth $auth) {}

    protected function endpoint(): string
    {
        $projectId = (string) config('services.fcm_v1.project_id');
        if (!$projectId) {
            throw new \RuntimeException('FCM project_id is not set. Check FCM_PROJECT_ID in .env');
        }
        return "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        $accessToken = $this->auth->getAccessToken();

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [ 'title' => $title, 'body' => $body ],
                'data' => array_map('strval', $data),

                'webpush' => [
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                        'icon'  => url('/favicon.ico'),   // اختياري
                    ],
                    'fcm_options' => [
                        'link' => url('/'),               // أين يفتح عند النقر (اختياري)
                    ],
                    'headers' => [ 'Urgency' => 'high' ],
                ],

                'android' => [ 'priority' => 'HIGH' ],
                'apns'    => [ 'headers' => ['apns-priority' => '10'] ],
            ],
        ];


        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(15)           // تجنّب الانتظار الطويل
            ->retry(2, 200)         // محاولتان إضافيتان
            ->post($this->endpoint(), $payload);

        // لوج مفيد للتشخيص
        Log::info('FCM response', [
            'status' => $response->status(),
            'body'   => $response->json(),
        ]);

        // معالجة أخطاء شائعة لتنظيف التوكن
        if ($response->failed()) {
            $j = $response->json() ?? [];
            $msg = json_encode($j, JSON_UNESCAPED_UNICODE);

            // إذا كان التوكن غير صالح، احذفيه
            if (is_array($j) && isset($j['error']['status'])) {
                $status = $j['error']['status'];
                if (in_array($status, ['UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
                    DeviceToken::where('token', $token)->delete();
                    Log::warning('Deleted invalid FCM token', ['status' => $status, 'token' => substr($token, 0, 12) . '...']);
                }
            }

            $response->throw(); // يرمي استثناء مع التفاصيل
        }
    }
}

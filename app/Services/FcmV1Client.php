<?php


namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\DeviceToken;

class FcmV1Client
{
    public function __construct(protected FcmV1Auth $auth)
    {
    }

    protected function endpoint(): string
    {
        $projectId = (string)config('services.fcm_v1.project_id');
        if (!$projectId) {
            throw new \RuntimeException('FCM project_id is not set.');
        }
        return "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        $resp = Http::withToken($this->auth->getAccessToken())
            ->acceptJson()
            ->timeout(15)
            ->retry(2, 200)
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

        Log::info('FCM response', [
            'token'  => $token,
            'status' => $resp->status(),
            'body'   => $resp->json(),
        ]);

        if ($resp->failed()) {
            $j      = $resp->json() ?? [];
            $status = $j['error']['status'] ?? null;

            if (
                $resp->status() === 404 ||
                in_array($status, [
                    'UNREGISTERED',
                    'INVALID_ARGUMENT',
                    'NOT_FOUND',
                    'UNAUTHENTICATED',
                ], true)
            ) {
                DeviceToken::where('token', $token)->delete();

                Log::warning('Deleted invalid FCM token', [
                    'token'      => $token,
                    'http_code'  => $resp->status(),
                    'fcm_status' => $status,
                ]);
                return;
            }
            if (in_array($resp->status(), [401, 403], true)) {
                Log::error('FCM auth error - check credentials', [
                    'http_code' => $resp->status(),
                    'fcm_status' => $status,
                ]);
                throw new \RuntimeException('FCM authentication failed.');
            }

            $resp->throw();
        }
    }

}

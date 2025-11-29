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
                        'body' => $body ?: '',
                    ],
                    'data' => array_map('strval', $data),
                ],
            ]);

        Log::info('FCM response', [
            'status' => $resp->status(),
            'body' => $resp->json(),
        ]);

        if ($resp->failed()) {

            $json = $resp->json() ?? [];
            $status = $json['error']['status'] ?? null;
            $code   = $json['error']['code'] ?? null;
            $message = $json['error']['message'] ?? null;

            // 🔥 حالات التوكن المنتهي / المحذوف / غير صالح
            $invalidTokenErrors = [
                'UNREGISTERED',
                'INVALID_ARGUMENT',
                'NOT_FOUND',
                'INVALID_REGISTRATION',
                'REGISTRATION_TOKEN_NOT_REGISTERED',
            ];

            if (
                $resp->status() === 404 ||   // HTTP 404
                $code === 404 ||             // FCM code 404
                in_array($status, $invalidTokenErrors, true)
            ) {
                DeviceToken::where('token', $token)->delete();

                Log::warning('Deleted invalid FCM token', [
                    'token'      => $token,
                    'http_code'  => $resp->status(),
                    'fcm_code'   => $code,
                    'fcm_status' => $status,
                    'fcm_message'=> $message,
                ]);

                // 🔥 مهم: لا ترمي Exception → دع الـ job يعتبر ناجح
                return;
            }

            // 🔥 حالة credential error
            if (in_array($resp->status(), [401, 403], true)) {
                Log::error('FCM auth error - check credentials', [
                    'http_code'  => $resp->status(),
                    'fcm_status' => $status,
                    'fcm_message'=> $message,
                ]);
                throw new \RuntimeException('FCM authentication failed.');
            }

            // غير ذلك → throw لكي يعيد المحاولة
            $resp->throw();
        }

    }
}

//
//namespace App\Services;
//
//use Illuminate\Support\Facades\Http;
//use Illuminate\Support\Facades\Log;
//use App\Models\DeviceToken;
//
//class FcmV1Client
//{
//    public function __construct(protected FcmV1Auth $auth) {}
//
//    protected function endpoint(): string
//    {
//        $projectId = (string) config('services.fcm_v1.project_id');
//        if (!$projectId) throw new \RuntimeException('FCM project_id is not set.');
//        return "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
//    }
//
//    public function sendToToken(string $token, string $title, string $body, array $data = []): void
//    {
//        $resp = Http::withToken($this->auth->getAccessToken())
//            ->acceptJson()->timeout(15)->retry(2, 200)
//            ->post($this->endpoint(), [
//                'message' => [
//                    'token'        => $token,
//                    'notification' => ['title' => $title, 'body' => $body],
//                    'data'         => array_map('strval', $data),
//                ],
//            ]);
//
//        Log::info('FCM response', ['status' => $resp->status(), 'body' => $resp->json()]);
//
//        if ($resp->failed()) {
//            $j = $resp->json() ?? [];
//            $status = $j['error']['status'] ?? null;
//
//            if (in_array($status, ['UNREGISTERED','INVALID_ARGUMENT'], true)) {
//                DeviceToken::where('token', $token)->delete();
//                Log::warning('Deleted invalid FCM token', ['status'=>$status]);
//                return;
//            }
//            if (in_array($resp->status(), [401,403], true)) {
//                Log::error('FCM auth error - check credentials');
//                throw new \RuntimeException('FCM authentication failed.');
//            }
//            $resp->throw(); // 429/5xx → اسمحي بإعادة المحاولة
//        }
//    }
//}

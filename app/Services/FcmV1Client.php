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
        $projectId = config('services.fcm_v1.project_id');
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

        Log::info('FCM response', [
            'token'  => $token,
            'status' => $response->status(),
            'body'   => $response->json(),
        ]);

        if (! $response->failed()) {
            return; // SUCCESS ✓
        }

        // -------------------------------
        // 🟥 معالجة الأخطاء
        // -------------------------------

        $json   = $response->json() ?? [];
        $error  = $json['error'] ?? [];
        $status = $error['status'] ?? null;
        $msg    = $error['message'] ?? null;

        // الحصول على errorCode من details إن وُجد
        $details    = $error['details'] ?? [];
        $detailCode = null;

        if (is_array($details)) {
            foreach ($details as $item) {
                if (isset($item['errorCode'])) {
                    $detailCode = $item['errorCode'];
                    break;
                }
            }
        }

        Log::error('FCM error', [
            'http_status' => $response->status(),
            'status'      => $status,
            'detail_code' => $detailCode,
            'message'     => $msg,
            'raw'         => $json,
        ]);

        // -------------------------------
        // 🟨 حالات التوكن غير صالح
        // -------------------------------

        $invalidCodes = ['UNREGISTERED', 'INVALID_ARGUMENT', 'NOT_FOUND'];

        $isInvalidToken =
            in_array($status, $invalidCodes, true) ||
            in_array($detailCode, $invalidCodes, true) ||
            (
                $response->status() === 404 &&
                $msg &&
                str_contains($msg, 'Requested entity was not found')
            );

        if ($isInvalidToken) {
            DeviceToken::where('token', $token)->delete();

            Log::warning('Deleted invalid FCM token', [
                'token'       => $token,
                'status'      => $status,
                'detail_code' => $detailCode,
            ]);

            return; // لا نرمي إكسبشن → عادي نكمل النظام
        }

        // -------------------------------
        // 🟥 مشكلة صلاحيات (كريدنشالز)
        // -------------------------------

        if (in_array($response->status(), [401, 403], true) || $status === 'UNAUTHENTICATED') {
            Log::error('FCM authentication failed', [
                'status'      => $status,
                'detail_code' => $detailCode,
                'message'     => $msg,
            ]);

            throw new \RuntimeException('FCM authentication failed.');
        }

        // -------------------------------
        // باقي الأخطاء → اسمحي بإعادة محاولة الـ queue
        // -------------------------------
        $response->throw();
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

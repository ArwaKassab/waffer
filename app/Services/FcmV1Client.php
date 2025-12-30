<?php

namespace App\Services;

use App\Services\Exceptions\InvalidFcmTokenException;
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

    private function maskToken(string $token): string
    {
        $token = (string) preg_replace('/\s+/', '', $token);
        $token = trim($token);

        if ($token === '') {
            return '[empty]';
        }

        // اعرض أول 6 وآخر 4 أحرف فقط
        $start = substr($token, 0, 6);
        $end   = substr($token, -4);

        return $start . '…' . $end;
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        // ✅ تطبيع التوكن (مهم جدًا لتفادي duplicate بسبب \n)
        $token = (string) preg_replace('/\s+/', '', $token);
        $token = trim($token);

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

        // ✅ في الإنتاج: لا نسجّل body كامل لكل نجاح عادة (اختياري)
        Log::info('FCM send result', [
            'http'  => $http,
            'ok'    => ! $response->failed(),
            'token' => $this->maskToken($token),
        ]);

        if (! $response->failed()) {
            return;
        }

        $error   = $json['error'] ?? [];
        $status  = $error['status'] ?? null;     // NOT_FOUND / UNAUTHENTICATED ...
        $message = $error['message'] ?? null;    // NotRegistered ...

        // Extract errorCode من details إن وجد
        $detailCode = null;
        foreach (($error['details'] ?? []) as $d) {
            if (is_array($d) && isset($d['errorCode'])) {
                $detailCode = $d['errorCode'];
                break;
            }
        }

        // ✅ لا تسجّل raw بالكامل في الإنتاج إلا عند الحاجة (أو اجعله debug)
        Log::warning('FCM send failed (parsed)', [
            'http'        => $http,
            'token'       => $this->maskToken($token),
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
            in_array($message, ['NotRegistered'], true) ||
            ($http === 404 && $status === 'NOT_FOUND');

        if ($isInvalidToken) {
            // لا تضع التوكن كاملًا داخل رسالة الاستثناء
            throw new InvalidFcmTokenException(
                "Invalid/expired token. http={$http} status={$status} detail={$detailCode} message={$message}"
            );
        }

        // ---------------------------
        // 🟥 Auth / Permission errors
        // ---------------------------
        if (
            in_array($http, [401, 403], true) ||
            in_array($status, ['UNAUTHENTICATED', 'PERMISSION_DENIED'], true)
        ) {
            throw new \RuntimeException('FCM authentication/permission failed.');
        }

        // ---------------------------
        // 🔁 Other errors (retryable)
        // ---------------------------
        $response->throw();
    }
}

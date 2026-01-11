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

        $start = substr($token, 0, 6);
        $end   = substr($token, -4);

        return $start . '…' . $end;
    }

    private function normalizeToken(string $token): string
    {
        $token = (string) preg_replace('/\s+/', '', $token);
        return trim($token);
    }

    private function parseError(\Illuminate\Http\Client\Response $response): array
    {
        $json = $response->json() ?? [];
        $error = $json['error'] ?? [];

        $status  = $error['status'] ?? null;   // NOT_FOUND / UNAUTHENTICATED / ...
        $message = $error['message'] ?? null;  // NotRegistered / Requested entity was not found. / ...

        $detailCode = null;
        foreach (($error['details'] ?? []) as $d) {
            if (is_array($d) && isset($d['errorCode'])) {
                $detailCode = $d['errorCode']; // UNREGISTERED ...
                break;
            }
        }

        return [
            'http'        => $response->status(),
            'status'      => $status,
            'message'     => $message,
            'detail_code' => $detailCode,
            'raw_error'   => $error,
        ];
    }

    private function isInvalidTokenError(array $err): bool
    {
        $http       = (int) ($err['http'] ?? 0);
        $status     = (string) ($err['status'] ?? '');
        $message    = (string) ($err['message'] ?? '');
        $detailCode = (string) ($err['detail_code'] ?? '');

        // 1) أدق إشارة: detail_code = UNREGISTERED
        if ($detailCode === 'UNREGISTERED') {
            return true;
        }

        // 2) رسائل شائعة للتوكن المنتهي
        if (stripos($message, 'NotRegistered') !== false) {
            return true;
        }

        // 3) INVALID_ARGUMENT عادةً توكن مخربط/غير صالح
        if ($detailCode === 'INVALID_ARGUMENT' || $status === 'INVALID_ARGUMENT') {
            return true;
        }

        // 4) NOT_FOUND مع 404 غالبًا توكن غير موجود (بشرط يكون endpoint صحيح)
        // نقيّدها بـ http 404 حتى ما نعتبر NOT_FOUND بغير سياق
        if ($http === 404 && $status === 'NOT_FOUND') {
            // بعض الأحيان تأتي "Requested entity was not found."
            return true;
        }

        return false;
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        $token = $this->normalizeToken($token);

        if ($token === '') {
            throw new InvalidFcmTokenException('Empty token.');
        }

        $response = Http::withToken($this->auth->getAccessToken())
            ->acceptJson()
            ->timeout(15)
            // retry فقط للأخطاء الشبكية/المؤقتة، وليس 404/401/403
            ->retry(2, 300, function ($exception, $request) {
                // إذا Exception شبكة/Timeout عادةً بنعيد
                return true;
            }, throw: false)
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

        Log::info('FCM send result', [
            'http'  => $http,
            'ok'    => ! $response->failed(),
            'token' => $this->maskToken($token),
        ]);

        if (! $response->failed()) {
            return;
        }

        $err = $this->parseError($response);

        Log::warning('FCM send failed (parsed)', [
            'http'        => $err['http'],
            'token'       => $this->maskToken($token),
            'status'      => $err['status'],
            'detail_code' => $err['detail_code'],
            'message'     => $err['message'],
        ]);

        // ✅ توكن غير صالح => نرمي Exception مخصصة حتى NotificationService يحذفه نهائيًا
        if ($this->isInvalidTokenError($err)) {
            throw new InvalidFcmTokenException(
                "Invalid/expired token. http={$err['http']} status={$err['status']} detail={$err['detail_code']} message={$err['message']}"
            );
        }

        // 🟥 Auth / Permission
        if (
            in_array((int) $err['http'], [401, 403], true) ||
            in_array((string) $err['status'], ['UNAUTHENTICATED', 'PERMISSION_DENIED'], true)
        ) {
            throw new \RuntimeException('FCM authentication/permission failed.');
        }

        // باقي الأخطاء
        $response->throw();
    }
}

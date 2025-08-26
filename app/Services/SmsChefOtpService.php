<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsChefOtpService
{
    public function sendOtp(string $phone, ?string $messageTemplate = null): array
    {
        $cfg = config('services.smschef');

        $payload = [
            'secret'  => $cfg['secret'],
            'type'    => $cfg['type'], // sms
            'message' => $messageTemplate ?: 'Your OTP is {{otp}}',
            'phone'   => $phone,
            'expire'  => (int) $cfg['expire'],
        ];

        // اسم باراميتر الجهاز وقيمته من .env (device_id أو host)
        if (!empty($cfg['device_param']) && !empty($cfg['device_value'])) {
            $payload[$cfg['device_param']] = $cfg['device_value'];
        }

        // لا تستخدمي sender إذا ترسلين من شريحة الجهاز
        if (!empty($cfg['sender'])) {
            $payload['sender'] = $cfg['sender'];
        }

        // أرسل الطلب وأرجع الرد كما هو (بدون retry للتشخيص)
        $res = \Illuminate\Support\Facades\Http::asForm()->post($cfg['base_url'] . '/api/send/otp', $payload);

        return [
            'http_status' => $res->status(),
            'body'        => $res->json() ?? $res->body(),
        ];
    }


    public function verifyOtp(string $otp): array
    {
        $cfg = config('services.smschef');
        $res = Http::retry(2, 300)->get($cfg['base_url'] . '/api/get/otp', [
            'secret' => $cfg['secret'],
            'otp'    => $otp,
        ]);
        if (!$res->successful()) {
            throw new \RuntimeException('رمز OTP غير صالح أو منتهي.');
        }
        return $res->json() ?? ['raw' => $res->body()];
    }

    public function generateOtp(int $digits = 6): string
    {
        // توليد رقم OTP آمن
        $min = 10 ** ($digits - 1);
        $max = (10 ** $digits) - 1;
        return (string) random_int($min, $max);
    }

    /**
     * إرسال SMS عادي عبر الجهاز المتصل باستخدام /api/send
     * هذا المدخل يدعم تميرير معرّف الجهاز (device_id أو host) حسب .env
     */
    // App/Services/SmsChefOtpService.php
    public function sendOtpViaDevice(string $phone, string $otp, ?string $prefixMessage = null): array
    {
        $cfg = config('services.smschef');

        $message = trim(($prefixMessage ?: 'رمز التفعيل الخاص بك') . ': ' . $otp . ' (صالح لمدة 5 دقائق)');

        $payload = [
            'secret'   => $cfg['secret'],
            'mode'     => 'devices',
            'device'   => $cfg['device_uuid'],
            'sim'      => (int)($cfg['sim'] ?? 1),
            'priority' => (int)($cfg['priority'] ?? 1),
            'phone'    => $phone,
            'message'  => $message,
        ];

        $url = rtrim($cfg['base_url'], '/') . '/api/send/sms';

        try {
            $res  = \Illuminate\Support\Facades\Http::asForm()->timeout(15)->post($url, $payload);
            $body = $res->json() ?? [];

            $apiStatus = (int)($body['status'] ?? 0);
            $messageId = $body['data']['messageId'] ?? null;

            // نجاح = تم قبول الرسالة في طابور الإرسال (Queued)
            $ok = $res->successful() && $apiStatus === 200 && !empty($messageId);

            return [
                'ok'          => $ok,
                'state'       => $ok ? 'queued' : 'failed',
                'message_id'  => $messageId,
                'http_status' => $res->status(),
                'provider_msg'=> $body['message'] ?? null,
            ];
        } catch (\Throwable $e) {
            return [
                'ok'    => false,
                'state' => 'exception',
                'error' => $e->getMessage(),
            ];
        }
    }



    public function getSentSms(int $limit = 10, int $page = 1, ?string $phone = null): array
    {
        $cfg = config('services.smschef');
        $base = rtrim($cfg['base_url'] ?? 'https://www.cloud.smschef.com', '/');

        $query = [
            'secret' => $cfg['secret'],
            'limit'  => $limit,
            'page'   => $page,
        ];
        if ($phone) {
            $query['phone'] = $phone; // إن كان الـAPI يدعم التصفية بالرقم
        }

        // جرّبي أيضاً بدون www إن احتجتِ
        $res = \Illuminate\Support\Facades\Http::get($base . '/api/get/sms.sent', $query);

        return [
            'http_status' => $res->status(),
            'body'        => $res->json() ?? $res->body(),
        ];
    }

    /** (اختياري) عرض الفاشلة إن كان مدعومًا */
    public function getFailedSms(int $limit = 10, int $page = 1): array
    {
        $cfg = config('services.smschef');
        $base = rtrim($cfg['base_url'] ?? 'https://www.cloud.smschef.com', '/');

        $query = [
            'secret' => $cfg['secret'],
            'limit'  => $limit,
            'page'   => $page,
        ];

        $res = \Illuminate\Support\Facades\Http::get($base . '/api/get/sms.failed', $query);
        return [
            'http_status' => $res->status(),
            'body'        => $res->json() ?? $res->body(),
        ];
    }


}

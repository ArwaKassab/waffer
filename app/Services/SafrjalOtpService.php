<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SafrjalOtpService
{
    public function sendOtp(string $phone, string $otp, ?string $title = null): array
    {
        $endpoint = config('services.safrjal.endpoint');
        $apiKey   = config('services.safrjal.key');
        $title    = $title ?: config('services.safrjal.title');

        if (!$apiKey) {
            return [
                'ok' => false,
                'reason' => 'config_missing',
                'status' => null,
                'body' => null,
            ];
        }

        $phone = ltrim(trim($phone), '+');

        try {
            $response = Http::timeout(15)
                ->withHeaders(['x-api-key' => $apiKey])
                ->post($endpoint, [
                    'phone' => $phone,
                    'otp'   => (string) $otp,
                    'title' => (string) $title,
                ]);
        } catch (\Throwable $e) {
            // فشل شبكة/timeout قبل ما يوصل رد من المزوّد
            return [
                'ok' => false,
                'reason' => 'network_error',
                'status' => null,
                'body' => $e->getMessage(),
            ];
        }

        $status = $response->status();
        $rawBody = $response->body();
        $json = $response->json();

        if ($response->successful()) {
            return [
                'ok' => true,
                'reason' => null,
                'status' => $status,
                'data' => $json,
            ];
        }

        // تصنيف السبب اعتمادًا على status + (code/error إن وجد)
        $reason = match (true) {
            $status === 401 || $status === 403 => 'auth_error',          // API key / صلاحيات
            $status === 429 => 'rate_limited',                           // حد أقصى
            $status >= 400 && $status < 500 => 'bad_request',            // بيانات غير صحيحة / رقم غير مدعوم...
            $status >= 500 => 'provider_server_error',                   // مشكلة عندهم
            default => 'unknown_error',
        };

        // لو المزوّد يرسل code داخلي (مثل INTERNAL_ERROR) نستفيد منه
        $providerCode = is_array($json) ? ($json['code'] ?? null) : null;
        if ($providerCode === 'INTERNAL_ERROR') {
            $reason = 'provider_internal_error';
        }

        return [
            'ok' => false,
            'reason' => $reason,
            'status' => $status,
            'provider_code' => $providerCode,
            'body' => $rawBody,
        ];
    }
}

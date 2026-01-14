<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SafrjalOtpService
{
    public function sendOtp(string $phone, string $otp, ?string $title = null): array
    {
        $endpoint = config('services.safrjal.endpoint');
        $apiKey   = config('services.safrjal.key');
        $title    = $title ?: config('services.safrjal.title');

        if (!$apiKey) {
            throw new RuntimeException('Missing SAFRJAL_API_KEY');
        }

        // تأكد من صيغة الهاتف: بدون + وبصيغة دولية مثل 9639xxxxxxxx
        $phone = ltrim(trim($phone), '+');

        $response = Http::timeout(15)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'x-api-key' => $apiKey,
            ])
            ->post($endpoint, [
                'phone' => $phone,
                'otp'   => (string) $otp,
                'title' => (string) $title,
            ]);

        if (!$response->successful()) {
            // خزّن هذا في logs للتشخيص
            throw new RuntimeException('Safrjal OTP failed: '.$response->status().' '.$response->body());
        }

        return $response->json() ?? ['success' => true];
    }
}

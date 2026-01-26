<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SafrjalOtpService
{public function sendOtp(string $phone, string $otp, ?string $title = null): array
{
    $endpoint = config('services.safrjal.endpoint');
    $apiKey   = config('services.safrjal.key');
    $title    = $title ?: config('services.safrjal.title');

    if (!$apiKey) {
        return ['ok' => false, 'error' => 'Missing SAFRJAL_API_KEY'];
    }

    $phone = ltrim(trim($phone), '+');

    $response = Http::timeout(15)
        ->withHeaders(['x-api-key' => $apiKey])
        ->post($endpoint, [
            'phone' => $phone,
            'otp'   => (string) $otp,
            'title' => (string) $title,
        ]);

    if (!$response->successful()) {
        return [
            'ok'     => false,
            'status' => $response->status(),
            'body'   => $response->body(),
        ];
    }

    return [
        'ok' => true,
        'data' => $response->json(),
    ];
}

}

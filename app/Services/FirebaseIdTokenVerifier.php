<?php

namespace App\Services;

use Kreait\Firebase\Auth;

class FirebaseIdTokenVerifier
{
    public function __construct(private Auth $auth) {}

    /**
     * @return array{uid:string, phone_e164:string}
     */
    public function verifyAndGetUidAndPhone(string $idToken): array
    {
        // يتحقق من صحة/توقيع/انتهاء التوكن ويُرجع claims عند النجاح
        $verifiedToken = $this->auth->verifyIdToken($idToken); // :contentReference[oaicite:2]{index=2}

        $uid = (string) $verifiedToken->claims()->get('sub');

        // أحياناً تكون موجودة في claims
        $phone = $verifiedToken->claims()->get('phone_number', null);

        // fallback: اجلب user record من Firebase
        if (!$phone) {
            $user = $this->auth->getUser($uid);
            $phone = $user->phoneNumber; // غالباً +9639xxxxxxx
        }

        if (!$uid || !$phone) {
            throw new \RuntimeException('Firebase token verified but missing uid/phone_number.');
        }

        return [
            'uid'        => $uid,
            'phone_e164' => (string) $phone,
        ];
    }
}

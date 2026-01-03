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
        $verifiedToken = $this->auth->verifyIdToken($idToken);

        $uid = (string) $verifiedToken->claims()->get('sub');

        $phone = $verifiedToken->claims()->get('phone_number', null);

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

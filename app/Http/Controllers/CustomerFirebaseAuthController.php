<?php

namespace App\Http\Controllers;

use App\Http\Requests\FirebaseRegisterCustomerRequest;
use App\Http\Requests\FirebaseResetPasswordRequest;
use App\Http\Resources\CustomerResource;
use App\Services\CustomerFirebaseAuthService;
use App\Services\FirebaseIdTokenVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;

class CustomerFirebaseAuthController extends Controller
{
    public function __construct(
        private FirebaseIdTokenVerifier $verifier,
        private CustomerFirebaseAuthService $firebaseAuthService
    ) {}

    /**
     * Register (OTP على الفرونت عبر Firebase) ثم الباك يعمل verify للتوكن وينشئ الحساب
     */
    public function register(FirebaseRegisterCustomerRequest $request): JsonResponse
    {
        $data = $request->validated();
        $visitorId = $request->cookie('visitor_id');

        // Rate limit على endpoint واحد
        $key = 'firebase-register:' . sha1(($request->ip() ?? 'ip') . '|' . substr($data['firebase_id_token'], 0, 20));
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json([
                'message' => 'محاولات كثيرة. حاول لاحقًا.',
                'retry_after_seconds' => RateLimiter::availableIn($key),
            ], 429);
        }
        RateLimiter::hit($key, 60);

        $claims = $this->verifier->verifyAndGetUidAndPhone($data['firebase_id_token']);

        $user = $this->firebaseAuthService->registerCustomer(
            $data,
            $claims['uid'],
            $claims['phone_e164'],
            $visitorId
        );

        // (اختياري) إصدار توكن نظامك مباشرة
        $token = $user->createToken('customer-token', ['customer'])->plainTextToken;

        return response()->json([
            'message' => 'تم إنشاء حسابك وتفعيل رقم الهاتف بنجاح عبر Firebase.',
            'token'   => $token,
            'user'    => new CustomerResource($user),
        ], 201);
    }

    /**
     * Reset Password (OTP على الفرونت عبر Firebase) ثم verify token ثم تغيير كلمة المرور
     */
    public function resetPassword(FirebaseResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        $key = 'firebase-reset:' . sha1(($request->ip() ?? 'ip') . '|' . substr($data['firebase_id_token'], 0, 20));
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'message' => 'محاولات كثيرة. حاول لاحقًا.',
                'retry_after_seconds' => RateLimiter::availableIn($key),
            ], 429);
        }
        RateLimiter::hit($key, 60);

        $claims = $this->verifier->verifyAndGetUidAndPhone($data['firebase_id_token']);

        $this->firebaseAuthService->resetPassword(
            $claims['phone_e164'],
            $data['new_password']
        );

        return response()->json([
            'message' => 'تم تغيير كلمة المرور بنجاح.',
            'success' => true,
        ], 200);
    }
}

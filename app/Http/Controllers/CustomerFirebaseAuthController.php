<?php

namespace App\Http\Controllers;

use App\Http\Requests\FirebaseCustomerRegisterRequest;
use App\Http\Requests\FirebaseCustomerResetPasswordRequest;
use App\Http\Resources\CustomerResource;
use App\Services\CustomerFirebaseAuthService;
use App\Services\FirebaseIdTokenVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;

class CustomerFirebaseAuthController extends Controller
{
    public function __construct(
        private FirebaseIdTokenVerifier $verifier,
        private CustomerFirebaseAuthService $service
    ) {}

    public function register(FirebaseCustomerRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $visitorId = $request->cookie('visitor_id');

        $key = 'firebase-register:' . sha1(($request->ip() ?? 'ip') . '|' . substr($data['firebase_id_token'], 0, 25));
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json([
                'message' => 'محاولات كثيرة. حاول لاحقًا.',
                'retry_after_seconds' => RateLimiter::availableIn($key),
            ], 429);
        }
        RateLimiter::hit($key, 60);

        // أهم سطر: Verify التوكن واستخراج رقم الهاتف الحقيقي من Firebase
        $claims = $this->verifier->verifyAndGetUidAndPhone($data['firebase_id_token']);

        // تجاهل phone القادم من الفرونت حتى لو أرسله
        $user = $this->service->register($data, $claims['uid'], $claims['phone_e164'], $visitorId);

        // الفرونت يتوقع token في response.data['token']
        $token = $user->createToken('customer-token', ['customer'])->plainTextToken;

        return response()->json([
            'message' => 'تم إنشاء حسابك وتفعيل رقم الهاتف بنجاح.',
            'token'   => $token,
            'user'    => new CustomerResource($user),
        ], 201);
    }

    public function resetPassword(FirebaseCustomerResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        $key = 'firebase-reset:' . sha1(($request->ip() ?? 'ip') . '|' . substr($data['firebase_id_token'], 0, 25));
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'message' => 'محاولات كثيرة. حاول لاحقًا.',
                'retry_after_seconds' => RateLimiter::availableIn($key),
            ], 429);
        }
        RateLimiter::hit($key, 60);

        $claims = $this->verifier->verifyAndGetUidAndPhone($data['firebase_id_token']);

        // الفرونت يرسل الحقل باسم password
        $this->service->resetPassword($claims['phone_e164'], $data['password']);

        // الفرونت يتحقق من success == true
        return response()->json([
            'success' => true,
            'message' => 'تم تحديث كلمة المرور بنجاح.',
        ], 200);
    }
}

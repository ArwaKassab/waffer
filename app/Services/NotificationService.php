<?php

namespace App\Services;

use App\Models\AppUserNotification;
use App\Models\DeviceToken;
use App\Services\Exceptions\InvalidFcmTokenException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(protected FcmV1Client $fcm) {}

    public function sendToUser(
        int $userId,
        string $type,
        string $title,
        ?string $body = null,
        ?int $orderId = null,
        array $data = [],
        ?string $appKey = null
    ): AppUserNotification {

        // 1) خزّن في DB (سجل الإشعار)
        $notification = AppUserNotification::create([
            'user_id'  => $userId,
            'type'     => $type,
            'title'    => $title,
            'body'     => $body,
            'order_id' => $orderId,
            'data'     => $data,
            'read_at'  => null,
        ]);

        // 2) جيب توكنات المستخدم (وصفّي حسب app_key إذا موجود)
        $tokensQuery = DeviceToken::where('user_id', $userId);

        if ($appKey) {
            $tokensQuery->where('app_key', $appKey);
        }

        $tokenRows = $tokensQuery->get(['id', 'token']);

        // 3) ابعث لكل توكن، وإذا توكن خربان احذفه بدون إفشال الإشعار للباقي
        foreach ($tokenRows as $tokenRow) {

            $token = (string) $tokenRow->token;

            // ✅ تطبيع: إزالة أي whitespace مثل \n أو مسافات
            $token = preg_replace('/\s+/', '', $token);
            $token = trim($token);

            // لو التوكن فاضي/مخربط احذف السجل
            if ($token === '') {
                $tokenRow->delete();
                continue;
            }

            try {
                $this->fcm->sendToToken(
                    $token,
                    $title,
                    $body ?? '',
                    [
                        'notification_id' => (string) $notification->id,
                        'type'            => $type,
                        'order_id'        => $orderId ? (string) $orderId : '',
                    ] + $data
                );
            } catch (InvalidFcmTokenException $e) {
                // ✅ توكن غير صالح: احذف السجل نفسه (بالـ id)
                $tokenRow->delete();

                Log::warning('Deleted invalid FCM token row', [
                    'row_id' => $tokenRow->id,
                    'reason' => $e->getMessage(),
                ]);

                continue;
            } catch (\RuntimeException $e) {
                // مشاكل auth/permission لازم تفشل (هذه مشكلة إعدادات وليس توكن)
                throw $e;
            } catch (\Throwable $e) {
                // أخطاء أخرى لا تمنع إرسال الباقي
                Log::error('FCM send failed for token row', [
                    'row_id' => $tokenRow->id,
                    'err'    => $e->getMessage(),
                ]);
                continue;
            }
        }

        return $notification;
    }

    public function markAllReadForUser(int $userId): void
    {
        AppUserNotification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);
    }

    public function markOneRead(int $userId, int $notificationId): void
    {
        AppUserNotification::where('user_id', $userId)
            ->where('id', $notificationId)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);
    }

    public function unreadCountForUser(int $userId): int
    {
        return AppUserNotification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }
}

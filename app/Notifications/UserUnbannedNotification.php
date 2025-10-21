<?php

namespace App\Notifications;

class UserUnbannedNotification extends BaseNotification
{
    public function toArray($n): array
    {
        return [
            'type'  => 'user_unbanned',
            'title' => 'تم رفع الحظر عن حسابك',
            'body'  => 'يمكنك المتابعة باستخدام التطبيق.',
        ];
    }

    public function toFcm($n): array
    {
        return [
            'notification' => [
                'title' => 'تم رفع الحظر',
                'body'  => 'يمكنك المتابعة باستخدام التطبيق.',
            ],
            'data' => [
                'type' => 'user_unbanned',
            ],
        ];
    }
}

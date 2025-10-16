<?php

namespace App\Notifications;

class UserBannedNotification extends BaseNotification
{
    public function __construct(public ?string $reason = null) {}

    public function toArray($notifiable): array
    {
        return [
            'type'   => 'user_banned',
            'title'  => 'تم حظر حسابك',
            'body'   => $this->reason ?: 'يرجى التواصل مع الدعم.',
            'reason' => $this->reason,
        ];
    }

    public function toFcm($notifiable): array
    {
        return [
            'notification' => [
                'title' => 'تم حظر حسابك',
                'body'  => $this->reason ?: 'يرجى التواصل مع الدعم.',
            ],
            'data' => [
                'type'   => 'user_banned',
                'reason' => (string) ($this->reason ?? ''),
            ],
        ];
    }
}

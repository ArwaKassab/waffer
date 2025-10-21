<?php

// app/Notifications/OrderStatusChanged.php
namespace App\Notifications;

class OrderStatusChanged extends BaseNotification
{
    public function __construct(public int $orderId, public string $status) {}

    public function toArray($n): array
    {
        return [
            'type'     => 'order_status_changed',
            'title'    => 'تحديث حالة الطلب',
            'body'     => "تم تغيير حالة طلبك #{$this->orderId} إلى {$this->status}",
            'order_id' => $this->orderId,
            'status'   => $this->status,
        ];
    }

    public function toFcm($n): array
    {
        return [
            'notification' => [
                'title' => 'تحديث حالة الطلب',
                'body'  => "طلب #{$this->orderId} ⇒ {$this->status}",
            ],
            'data' => [
                'type'     => 'order_status_changed',
                'order_id' => (string) $this->orderId,
                'status'   => $this->status,
            ],
        ];
    }
}

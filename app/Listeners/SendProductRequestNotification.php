<?php
namespace App\Listeners;

use App\Events\ProductRequestReviewed;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendProductRequestNotification implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(protected NotificationService $notifications) {}

    public function handle(ProductRequestReviewed $event): void
    {
        $req = $event->request;

        $storeId = $req->store_id; // صاحب المنتج
        if (!$storeId) {
            return;
        }

        // عنوان الإشعار
        $title = $event->approved
            ? 'تمت الموافقة على طلبك التابع للمنتج'
            : 'تم رفض طلبك التابع للمنتج';

        // محتوى الإشعار
        $body = "نوع الطلب: {$req->action} | رقم الطلب: {$req->id}";

        // إرسال
        $this->notifications->sendToUser(
            userId: $storeId,
            type: 'product_request_review',
            title: $title,
            body: $body,
            orderId: $req->id,
            data: [
                'request_id' => (string) $req->id,
                'action'     => $req->action,
                'status'     => $event->approved ? 'approved' : 'rejected',
            ]
        );
    }
}

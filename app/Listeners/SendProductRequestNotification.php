<?php

namespace App\Listeners;

use App\Events\ProductRequestReviewed;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

class SendProductRequestNotification implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(protected NotificationService $notifications) {}

    public function handle(ProductRequestReviewed $event): void
    {
        $req = $event->request;

        $storeId = $req->store_id;
        if (!$storeId) {
            return;
        }
        
        $productName = null;

        if (method_exists($req, 'relationLoaded') && $req->relationLoaded('product')) {
            $productName = optional($req->product)->name;
        } elseif (isset($req->product) && is_object($req->product)) {
            $productName = optional($req->product)->name;
        } elseif (isset($req->product_name)) {
            $productName = $req->product_name;
        }

        $productName = $productName ? trim((string) $productName) : null;
        $actionLabel = $this->actionLabel((string) $req->action);
        $shortName = $productName ? Str::limit($productName, 40) : 'المنتج';

        if ($event->approved) {
            $title = 'تهانينا!';
            $body  = "تمت الموافقة على طلب {$actionLabel} للـ {$shortName}.";
        } else {
            $title = 'للأسف';
            $body  = "تم رفض طلب {$actionLabel} للـ {$shortName}.";
        }

        $this->notifications->sendToUser(
            userId: $storeId,
            type: 'product_request_review',
            title: $title,
            body: $body,
            orderId: $req->id,

            data: [
                'request_id'   => (string) $req->id,
                'action'       => (string) $req->action,
                'action_label' => $actionLabel,
                'status'       => $event->approved ? 'approved' : 'rejected',
                'product_name' => $productName,
            ]
        );
    }

    private function actionLabel(string $action): string
    {
        return match ($action) {
            'create', 'add', 'store'     => 'إضافة',
            'update', 'edit'            => 'تعديل',
            'delete', 'remove', 'destroy'=> 'حذف',
            default                      => 'معالجة',
        };
    }
}

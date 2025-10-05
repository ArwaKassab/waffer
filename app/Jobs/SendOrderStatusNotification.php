<?php

// app/Jobs/SendOrderStatusNotification.php
namespace App\Jobs;

use App\Models\DeviceToken;
use App\Models\Notification;
use App\Models\User;
use App\Services\FcmV1Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendOrderStatusNotification implements ShouldQueue
{
    use Dispatchable, Queueable;

    public $tries = 3;
    public function backoff(){ return [10,30,90]; }

    public function __construct(
        public int $userId,
        public int $orderId,
        public string $status
    ) {}

    public function handle(FcmV1Client $fcm): void
    {
        $user = User::find($this->userId);
        if (!$user) return;

        $n = Notification::create([
            'user_id' => $user->id,
            'title'   => 'تحديث حالة الطلب',
            'body'    => "حالة طلبك أصبحت: {$this->status}",
            'type'    => 'order_status',
            'order_id'=> $this->orderId,
            'data'    => ['status'=>$this->status],
        ]);

        $tokens = DeviceToken::where('user_id',$user->id)->pluck('token')->all();

        foreach ($tokens as $t) {
            try {
                $fcm->sendToToken($t, $n->title, $n->body ?? '', [
                    'orderId'        => (string)$this->orderId,
                    'status'         => $this->status,
                    'notificationId' => (string)$n->id,
                ]);
            } catch (\Throwable $e) {
                // إذا رد FCM بـ NotRegistered/InvalidRegistration احذفي التوكن
                // مثال:
                // if (str_contains($e->getMessage(), 'UNREGISTERED')) DeviceToken::where('token',$t)->delete();
            }
        }
    }
}

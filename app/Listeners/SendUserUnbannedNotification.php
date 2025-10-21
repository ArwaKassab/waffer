<?php

namespace App\Listeners;

use App\Events\UserUnbanned;
use App\Notifications\UserUnbannedNotification;

class SendUserUnbannedNotification
{
    public function handle(UserUnbanned $event): void
    {
        $event->user->notify(new \App\Notifications\UserUnbannedNotification());
    }
}

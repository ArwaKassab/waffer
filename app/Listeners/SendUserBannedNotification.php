<?php

namespace App\Listeners;

use App\Events\UserBanned;
use App\Notifications\UserBannedNotification;

class SendUserBannedNotification
{
    public function handle(UserBanned $event): void
    {
        $event->user->notify(new UserBannedNotification($event->reason));
    }
}

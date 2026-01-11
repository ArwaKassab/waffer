<?php

namespace App\Console\Commands;

use App\Models\DeviceToken;
use Illuminate\Console\Command;

class FcmPurgeBadTokens extends Command
{
    protected $signature = 'fcm:purge-bad-tokens';
    protected $description = 'Delete obviously bad FCM tokens (empty/whitespace) permanently';

    public function handle(): int
    {
        $rows = DeviceToken::get(['id', 'token']);
        $deleted = 0;

        foreach ($rows as $r) {
            $token = preg_replace('/\s+/', '', (string) $r->token);
            $token = trim($token);

            if ($token === '') {
                DeviceToken::whereKey($r->id)->delete();
                $deleted++;
            }
        }

        $this->info("Deleted bad token rows: {$deleted}");
        return self::SUCCESS;
    }
}

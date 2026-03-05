<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // لتسريع WHERE type='customer'
            $table->index('type');

            // لتسريع wallet_balance > 0
            $table->index('wallet_balance');

            // الأفضل للاستعلامات تبعك (مركّب)
            $table->index(['type', 'wallet_balance']);
            $table->index(['type', 'wallet_balance', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['wallet_balance']);
            $table->dropIndex(['type', 'wallet_balance']);
            $table->dropIndex(['type', 'wallet_balance', 'deleted_at']);
        });
    }
};

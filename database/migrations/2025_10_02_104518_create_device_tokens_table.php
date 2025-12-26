<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $t) {
            $t->id();

            $t->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $t->string('token', 512)->unique();
            $t->string('app_key', 30)->index();  //customer | store
            $t->string('package_name', 150)->nullable()->index(); // com.wafir / com.wafir.store

            $t->string('device_type', 50)->nullable(); // android / ios / web
            $t->string('app_version', 50)->nullable();
            $t->timestamp('last_used_at')->nullable();

            $t->uuid('visitor_id')->nullable()->index();

            $t->timestamps();

            $t->index(['user_id', 'app_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};

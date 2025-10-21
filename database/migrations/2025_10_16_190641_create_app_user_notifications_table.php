<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_app_user_notifications_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_user_notifications', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('user_id')->index();
            $t->string('type')->index();
            $t->string('title');
            $t->string('body')->nullable();
            $t->unsignedBigInteger('order_id')->nullable()->index();
            $t->json('data')->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
            $t->index(['user_id','created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('app_user_notifications'); }
};

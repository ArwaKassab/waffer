<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->onDelete('cascade');
            $t->string('title');
            $t->string('body')->nullable();
            $t->string('type')->nullable();        // مثال: order_status
            $t->unsignedBigInteger('order_id')->nullable();
            $t->json('data')->nullable();          // {status: on_way, ...}
            $t->timestamp('read_at')->nullable();  // مقروء/غير مقروء
            $t->timestamps();

            $t->index(['user_id','created_at']);
        });
    }
    public function down() { Schema::dropIfExists('notifications'); }
};

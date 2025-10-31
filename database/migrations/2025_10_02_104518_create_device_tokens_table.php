<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('device_tokens', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $t->string('token')->unique();
            $t->string('device_type')->nullable();
            $t->string('app_version')->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->uuid('visitor_id')->nullable()->index();

            $t->timestamps();

            $t->index(['user_id']);
        });
    }
    public function down() { Schema::dropIfExists('device_tokens'); }
};

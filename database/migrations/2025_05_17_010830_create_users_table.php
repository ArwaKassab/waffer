<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 20)->unique();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('whatsapp_phone')->nullable();
            $table->string('password');
            $table->foreignId('area_id')
                ->nullable()
                ->constrained('areas');
            $table->string('email')->unique()->nullable();
            $table->string('image')->nullable();
            $table->time('open_hour')->nullable();
            $table->time('close_hour')->nullable();
            $table->boolean('status')->default(true);
            $table->decimal('wallet_balance', 10, 2)->default(0);
            $table->enum('type', ['admin', 'sub_admin', 'customer', 'store']);
            $table->text('note')->nullable();
            $table->boolean('is_banned')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }

};

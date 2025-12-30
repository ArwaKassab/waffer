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
            $table->enum('type', ['admin', 'sub_admin', 'customer', 'store']);
            $table->string('user_name')->nullable()->unique();
            $table->string('phone', 20);
            $table->unique(['phone', 'type'], 'users_phone_type_unique');
            $table->string('store_contact_phone')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('password');
            $table->foreignId('area_id')->nullable()->constrained('areas');
            $table->string('email')->nullable()->unique();
            $table->string('image')->nullable();
            $table->time('open_hour')->nullable();
            $table->time('close_hour')->nullable();
            $table->boolean('status')->default(true);
            $table->decimal('wallet_balance', 10, 2)->default(0);
            $table->text('note')->nullable();
            $table->boolean('is_banned')->default(false);
            $table->string('phone_shadow', 20)->nullable();
            $table->index('phone_shadow');
            $table->string('firebase_uid', 128)->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
}

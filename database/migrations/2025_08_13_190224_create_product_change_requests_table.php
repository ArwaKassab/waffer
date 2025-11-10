<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('product_change_requests', function (Blueprint $table) {
            $table->id();

            $table->enum('action', ['create','update','delete'])->index();

            // للـ update: قد يكون موجود، للـ create: بيكون NULL
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();

            // للـ create: لازم store_id (عدّل اسم الجدول لو مو "stores")
            $table->foreignId('store_id')->nullable()->constrained('users')->cascadeOnDelete();


            $table->enum('status', ['pending','approved','rejected'])->index()->default('pending');

            // الحقول المقترحة/المعدّلة
            $table->string('name')->nullable();
            $table->text('details')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->enum('status_value', ['available','not_available'])->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->string('unit', 50)->nullable();
            $table->text('image')->nullable();

            // لقفل تفاؤلي في حالة update
            $table->timestamp('product_updated_at_snapshot')->nullable();

            $table->text('review_note')->nullable();
            $table->timestamps();
        });


    }

    public function down(): void
    {}

};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemsTable extends Migration
{
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('store_id')->constrained('users');
            $table->integer('quantity');
            $table->enum('status', ['انتظار', 'مقبول', 'يجهز', 'حضر', 'في الطريق' ,'مستلم', 'مرفوض'])->default('انتظار');
            $table->decimal('total_price', 10, 2);
            $table->decimal('total_price_after_discount', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('unit_price_after_discount', 10, 2);
            $table->decimal('discount_value', 10, 2);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_items');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users'); // الزبون
            $table->foreignId('area_id')->constrained('areas');
            $table->foreignId('address_id')->constrained('addresses');
            $table->decimal('total_product_price', 10, 2);
            $table->decimal('delivery_fee', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->date('date');
            $table->time('time');
            $table->enum('status', ['pending', 'preparing', 'on_the_way', 'completed', 'canceled'])->default('pending');
            $table->enum('payment_method', ['cash', 'wallet'])->default('cash');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
}

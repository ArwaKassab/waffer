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
            $table->decimal('discount_fee', 10, 2);
            $table->decimal('totalAfterDiscount', 10, 2);
            $table->decimal('delivery_fee', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->date('date');
            $table->time('time');
            $table->enum('status', ['انتظار', 'مقبول', 'يجهز', 'حضر', 'في الطريق' ,'مستلم', 'مرفوض'])->default('انتظار');
            $table->enum('payment_method', ['نقدي', 'محفظة'])->default('نقدي');
            $table-> timestamp('wallet_deducted_at')->nullable();
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

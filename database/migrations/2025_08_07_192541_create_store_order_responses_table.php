<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create('store_order_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['مقبول', 'مرفوض']);
            $table->text('reason')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->decimal('store_total_invoice', 10, 2);
            $table->timestamps();

            $table->unique(['order_id', 'store_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_order_responses');
    }
};

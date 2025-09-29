<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->text('image')->nullable();
            $table->enum('status', ['available', 'not_available'])->default('available');
            $table->decimal('quantity', 8, 2);
            $table->enum('unit', ['غرام', 'كيلوغرام', 'قطعة', 'لتر'])->default('غرام');
            $table->text('details')->nullable();
            $table->foreignId('store_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
}

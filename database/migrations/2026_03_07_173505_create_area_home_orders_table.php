<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('area_home_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('area_id')->constrained('areas');

            $table->enum('entity_type', ['category', 'store']);
            $table->unsignedBigInteger('entity_id');

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['area_id', 'entity_type', 'entity_id'], 'area_home_unique');
            $table->index(['area_id', 'entity_type', 'is_active', 'sort_order'], 'area_home_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_home_orders');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        $products = DB::table('products')->get();

        foreach ($products as $product) {
            $unit = DB::table('units')
                ->where('name', $product->unit)
                ->first();

            if ($unit) {
                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['unit_id' => $unit->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_id', function (Blueprint $table) {
            //
        });
    }
};

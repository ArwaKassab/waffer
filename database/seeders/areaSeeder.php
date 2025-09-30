<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        // عطّل القيود مؤقتًا (MySQL)
        Schema::disableForeignKeyConstraints();
        DB::table('areas')->truncate();
        Schema::disableForeignKeyConstraints();

        $now = now(); // احذفي created_at/updated_at إذا جدولك ما فيه timestamps

        DB::table('areas')->insert([
            [
                'name'               => 'مزة',
                'delivery_fee'       => 10000,
                'free_delivery_from' => 100000,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'name'               => 'ببيلا',
                'delivery_fee'       => 8000,
                'free_delivery_from' => 80000,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
            [
                'name'               => 'ركن الدين',
                'delivery_fee'       => 6000,
                'free_delivery_from' => 60000,
                'created_at'         => $now,
                'updated_at'         => $now,
            ],
        ]);
    }
}

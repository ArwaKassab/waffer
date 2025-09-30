<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdsTableSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('ads')->truncate();
        Schema::enableForeignKeyConstraints();

        $now = now();

        DB::table('ads')->insert([
            ['image' => 'ads/phone.jpg', 'created_at' => $now, 'updated_at' => $now],
            ['image' => 'ads/camera.jpg', 'created_at' => $now, 'updated_at' => $now],
            ['image' => 'ads/laptop.jpg', 'created_at' => $now, 'updated_at' => $now],
            ['image' => 'ads/smart wash.jpg', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}

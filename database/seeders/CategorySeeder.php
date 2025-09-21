<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('categories')->insert([
            [
                'name' => 'مواد غذائية',
                'image' => 'categories/غذائية.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'خضار',
                'image' => 'categories/خضار.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'معجنات',
                'image' => 'categories/معجنات.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

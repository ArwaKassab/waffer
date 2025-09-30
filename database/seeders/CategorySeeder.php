<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Category::truncate();
        Schema::enableForeignKeyConstraints();

        $rows = [
            ['name' => 'مواد غذائية', 'image' => 'categories/غذائية.jpg'],
            ['name' => 'خضار',       'image' => 'categories/خضار.jpg'],
            ['name' => 'معجنات',     'image' => 'categories/معجنات.jpg'],
        ];

        foreach ($rows as $row) {
            Category::create($row);
        }
    }
}

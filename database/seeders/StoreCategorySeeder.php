<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreCategorySeeder extends Seeder
{
    public function run()
    {

        DB::table('store_category')->truncate();


        $storeCategoryData = [
            ['store_id' => 1, 'category_id' => 1],
            ['store_id' => 2, 'category_id' => 2],
            ['store_id' => 3, 'category_id' => 3],

            ['store_id' => 4, 'category_id' => 1],
            ['store_id' => 5, 'category_id' => 2],
            ['store_id' => 6, 'category_id' => 3],

            ['store_id' => 7, 'category_id' => 1],
            ['store_id' => 8, 'category_id' => 2],
            ['store_id' => 9, 'category_id' => 3],

        ];


        DB::table('store_category')->insert($storeCategoryData);
    }
}


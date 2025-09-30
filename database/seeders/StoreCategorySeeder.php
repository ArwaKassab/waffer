<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Category;

class StoreCategorySeeder extends Seeder
{
    public function run(): void
    {
        // نظّف الجدول بأمان
        Schema::disableForeignKeyConstraints();
        DB::table('store_category')->truncate();
        Schema::enableForeignKeyConstraints();

        // خرائط اسم المتجر ← اسم التصنيف
        $map = [
            'خضار مزة'            => 'خضار',
            'معجنات مزة'          => 'معجنات',
            'مواد غذائية مزة'     => 'مواد غذائية',

            'خضار ببيلا'          => 'خضار',
            'معجنات ببيلا'        => 'معجنات',
            'مواد غذائية ببيلا'   => 'مواد غذائية',

            'خضار ركن الدين'      => 'خضار',
            'معجنات ركن الدين'    => 'معجنات',
            'مواد غذائية ركن الدين'=> 'مواد غذائية',
        ];

        foreach ($map as $storeName => $categoryName) {
            $storeId = User::where('type', 'store')->where('name', $storeName)->value('id');
            $catId   = Category::where('name', $categoryName)->value('id');

            if ($storeId && $catId) {
                DB::table('store_category')->insert([
                    'store_id'    => $storeId,
                    'category_id' => $catId,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }
}

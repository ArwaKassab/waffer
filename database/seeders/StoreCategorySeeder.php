<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreCategorySeeder extends Seeder
{
    public function run()
    {
        DB::table('store_category')->truncate();

        // بيانات افتراضية للتصنيفات (تأكد من أن لديك تصنيفات موجودة في قاعدة البيانات)
        $categories = DB::table('categories')->pluck('id');

        // بيانات افتراضية للمستخدمين (المتاجر)
        $stores = DB::table('users')->where('type', 'store')->pluck('id');  // افترضنا أن المتاجر هم الـ users الذين لديهم role = 'store'

        // إضافة البيانات إلى جدول الربط store_category
        foreach ($stores as $store_id) {
            foreach ($categories as $category_id) {
                DB::table('store_category')->insert([
                    ['store_id' => 7, 'category_id' => 1],  // ربط متجر 1 مع تصنيف 1
                    ['store_id' => 6, 'category_id' => 2],  // ربط متجر 1 مع تصنيف 2
                    ['store_id' => 8, 'category_id' => 3],  // ربط متجر 2 مع تصنيف 3
                ]);
            }
        }
    }
}

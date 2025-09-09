<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreCategorySeeder extends Seeder
{
    public function run()
    {
        // حذف جميع السجلات القديمة في جدول store_category
        DB::table('store_category')->truncate();

        // ربط المتاجر مع التصنيفات
        $storeCategoryData = [
            ['store_id' => 7, 'category_id' => 1],  // ربط متجر 1 مع تصنيف 1
            ['store_id' => 6, 'category_id' => 2],  // ربط متجر 1 مع تصنيف 2
            ['store_id' => 8, 'category_id' => 3],  // ربط متجر 2 مع تصنيف 3
            // أضف المزيد من الربط كما ترغبين
        ];

        // إضافة البيانات إلى جدول الربط store_category
        DB::table('store_category')->insert($storeCategoryData);
    }
}


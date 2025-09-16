<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class CategorySeeder extends Seeder
{
    public function run()
    {
        // تخزين الصورة في مجلد categories
        $imagePath = storage_path('app/public/categories'); // مسار التخزين المحلي
        if (!File::exists($imagePath)) {
            File::makeDirectory($imagePath, 0777, true); // إنشاء المجلد إذا لم يكن موجود
        }

        // مثال لتحميل صورة من مسار محلي أو URL
        $image = file_get_contents(public_path('images/category_image.jpg'));  // قم بتغيير المسار حسب الصورة الفعلية لديك
        $imageName = 'category_image.jpg';
        Storage::put('public/categories/' . $imageName, $image);  // تخزين الصورة في مجلد categories

        // إضافة الفئات مع مسار الصورة
        DB::table('categories')->insert([
            ['name' => 'معجنات', 'image' => 'storage/categories/' . $imageName],
            ['name' => 'خضار', 'image' => 'storage/categories/' . $imageName],
            ['name' => 'مواد غذائية', 'image' => 'storage/categories/' . $imageName],
        ]);
    }
}


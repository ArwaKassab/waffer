<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LinksTableSeeder extends Seeder
{
    public function run(): void
    {
        // حذف جميع السجلات القديمة في جدول store_category
        DB::table('links')->truncate();

        DB::table('links')->insert([
            [
                'type' => 'phone',
                'title' => '0965885266',
                'link'  => '00963965885266',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'whatsapp',
                'title' => 'واتساب',
                'link'  => 'https://wa.me/963965885266',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'telegram',
                'title' => 'تليغرام',
                'link'  => 'https://t.me/Wafirdelivery',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'facebook',
                'title' => 'فيسبوك',
                'link'  => 'https://www.facebook.com/profile.php?id=61577646494156',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'instagram',
                'title' => 'انستغرام',
                'link'  => 'https://www.instagram.com/wafirdelivery?utm_source=qr&igsh=MXYzYTdxdmw3a2xmZA==',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'email',
                'title' => 'wafirdelivry@gmail.com',
                'link'  => 'wafirdelivry@gmail.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LinksTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('links')->insert([
            [
                'title' => '00963965885266',
                'link'  => '00963965885266',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'واتساب',
                'link'  => 'https://wa.me/963965885266',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'تيليغرام',
                'link'  => 'https://t.me/Wafirdelivery',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'فيسبوك',
                'link'  => 'https://www.facebook.com/profile.php?id=61577646494156',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'انستاغرام',
                'link'  => 'https://www.instagram.com/wafirdelivery?utm_source=qr&igsh=MXYzYTdxdmw3a2xmZA==',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'wafirdelivry@gmail.com',
                'link'  => 'wafirdelivry@gmail.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

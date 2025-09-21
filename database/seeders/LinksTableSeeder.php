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
                'title' => 'phone',
                'link'  => '00963965885266',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Whatsapp',
                'link'  => 'https://wa.me/963965885266',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'telegram',
                'link'  => '@Wafirdelivery',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'facebook',
                'link'  => 'https://www.facebook.com/profile.php?id=61577646494156',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'instagram',
                'link'  => 'https://www.instagram.com/wafirdelivery?utm_source=qr&igsh=MXYzYTdxdmw3a2xmZA==',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'email',
                'link'  => 'wafirdelivry@gmail.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

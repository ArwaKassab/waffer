<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdsTableSeeder extends Seeder
{
    public function run()
    {
        // إضافة بيانات للجدول
        DB::table('ads')->insert([
            [
                'image' => 'ads/1vEBH4lHVpQbuaAZdUIHj8wI5STG6v6FWUpRVBeo.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'image' => 'ads/NlQTCbYXOOVzLoVI3ycfAYnEvl8mo2AJ9WntxGli.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'image' => 'ads/qZlgcKEvXeFUu76pwgMpoW8PoE3O83M0aNp5Z5OU.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'image' => 'ads/vsGlsuV7GuTQ1qca4pObYVEzbMcMtYcf1DWXeNID.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

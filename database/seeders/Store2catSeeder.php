<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class Store2catSeeder extends Seeder
{
    public function run(): void
    {

        $note = 'ستجد لدينا كل ما تحتاج';

        $stores = [
            [
                'name' => 'خضار ومواد غذائية مزة',
                'phone' => '00963935971514',
                'password' => Hash::make('12345678'),
                'area_id' => 1,
                'type' => 'store',
                'status' => true,
                'open_hour' => '08:00:00',
                'close_hour' => '22:00:00',
                'image' => 'stores/خضار.jpg',
                'note'  => $note,
            ],
            [
                'name' => 'خضار ومواد غذائية ببيلا',
                'phone' => '00963935971525',
                'password' => Hash::make('12345678'),
                'area_id' => 2,
                'type' => 'store',
                'status' => true,
                'open_hour' => '08:00:00',
                'close_hour' => '22:00:00',
                'image' => 'stores/خضار.jpg',
                'note'  => $note,
            ],

            [
                'name' => 'خضار ومواد غذائية ركن الدين',
                'phone' => '00963935971534',
                'password' => Hash::make('12345678'),
                'area_id' => 3,
                'type' => 'store',
                'status' => true,
                'open_hour' => '08:00:00',
                'close_hour' => '22:00:00',
                'image' => 'stores/خضار.jpg',
                'note'  => $note,
            ],

        ];

        foreach ($stores as $store) {
            User::create($store);
        }
    }
}

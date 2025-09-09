<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class StoreSeeder extends Seeder
{
    public function run()
    {
        $stores = [
            [
                'name' => 'خضار مزة',
                'phone' => '00963935971522',
                'password' => Hash::make('12345678'),
                'area_id' => 1,
                'type' => 'store',
                'status' => true,
            ],
            [
                'name' => 'معجنات ببيلا',
                'phone' => '00963935971544',
                'password' => Hash::make('12345678'),
                'area_id' => 2,
                'type' => 'store',
                'status' => true,
            ],
            [
                'name' => 'مواد غذائية ركن الدين',
                'phone' => '00963935971524',
                'password' => Hash::make('12345678'),
                'area_id' => 3,
                'type' => 'store',
                'status' => true,
            ],
        ];

        foreach ($stores as $store) {
            User::create($store);
        }
    }
}

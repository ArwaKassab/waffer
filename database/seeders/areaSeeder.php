<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class areaSeeder extends Seeder
{
    public function run()
    {
        DB::table('links')->truncate();
        Area::create([
            'name' => 'مزة',
            'delivery_fee' => '10000',
            'free_delivery_from' => '100000',

        ],
            [
            'name' => 'ببيلا',
            'delivery_fee' => '8000',
            'free_delivery_from' => '80000',

            ],
            [
            'name' => 'ركن الدين',
            'delivery_fee' => '6000',
            'free_delivery_from' => '60000',

        ]);
    }

}

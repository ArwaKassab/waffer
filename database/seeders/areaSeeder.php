<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class areaSeeder extends Seeder
{
    public function run()
    {
        Area::create([
            'name' => 'مزة',
            ],
            [
            'name' => 'ببيلا',
            ],
            [
            'name' => 'ركن الدين',

        ]);
    }

}

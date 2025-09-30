<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Product::truncate();
        Schema::enableForeignKeyConstraints();

        $areas = ['مزة', 'ببيلا', 'ركن الدين'];

        $productsByType = [
            'خضار' => [
                ['name' => 'موز',            'price' => 15000, 'image' => 'products/موز.jpg',            'quantity' => 1,   'unit' => 'كيلوغرام', 'details' => null],
                ['name' => 'كرز',            'price' => 30000, 'image' => 'products/كرز.jpg',            'quantity' => 1,   'unit' => 'كيلوغرام', 'details' => null],
            ],
            'معجنات' => [
                ['name' => 'جبنة',          'price' => 1500,  'image' => 'products/جبنة.jpg',          'quantity' => 1,   'unit' => 'قطعة',      'details' => null],
                ['name' => 'بيتزا',         'price' => 30000, 'image' => 'products/بيتزا.jpg',         'quantity' => 1,   'unit' => 'قطعة',      'details' => null],
            ],
            'مواد غذائية' => [
                ['name' => 'مرتديلا',        'price' => 10000, 'image' => 'products/مرتديلا.jpg',        'quantity' => 1,   'unit' => 'قطعة',      'details' => null],
                ['name' => 'معجون الطماطم',  'price' => 25000, 'image' => 'products/معجون الطماطم.jpg', 'quantity' => 350, 'unit' => 'غرام',      'details' => null],
            ],
        ];

        foreach ($areas as $areaName) {
            foreach ($productsByType as $typePrefix => $products) {


                $storeName = "{$typePrefix} {$areaName}";

                // جيبي المتجر
                $store = User::query()
                    ->where('type', 'store')
                    ->where('name', $storeName)
                    ->first(['id']);

                if (!$store) {
                    continue;
                }

                // أضِف المنتجات لهذا المتجر
                foreach ($products as $p) {
                    Product::create([
                        'store_id' => $store->id,
                        'name'     => $p['name'],
                        'price'    => $p['price'],
                        'image'    => $p['image'],
                        'status'   => 'available',
                        'quantity' => $p['quantity'],
                        'unit'     => $p['unit'],
                        'details'  => $p['details'],
                    ]);
                }
            }
        }
    }
}

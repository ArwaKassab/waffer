<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class StoreSeeder extends Seeder
{
    public function run()
    {
        // حذف المتاجر القديمة فقط
        DB::table('users')->where('type', 'store')->delete();

        $stores = [
            [
                'name' => 'خضار مزة',
                'phone' => '00963935971511',
                'password' => Hash::make('12345678'),
                'area_id' => 1,
                'type' => 'store',
                'status' => true,
                'open_hour' => '08:00:00',
                'close_hour' => '22:00:00',
                'image' => 'stores/خضار.jpg',
                'note' =>'ستجد لدينا كل كا تحتاج',

            ],
            [
                'name' => 'معجنات مزة',
                'phone' => '00963935971512',
                'password' => Hash::make('12345678'),
                'area_id' => 1,
                'type' => 'store',
                'status' => true,
                'open_hour' => '09:00:00',
                'close_hour' => '23:00:00',
                'image' => 'stores/معجنات.jpg',
                'note' =>'ستجد لدينا كل كا تحتاج',


            ],
            [
                'name' => 'مواد غذائية مزة',
                'phone' => '00963935971513',
                'password' => Hash::make('12345678'),
                'area_id' => 1,
                'type' => 'store',
                'status' => true,
                'open_hour' => '07:30:00',
                'close_hour' => '21:00:00',
                'image' => 'stores/غذائية.jpg',
                'note' =>'ستجد لدينا كل كا تحتاج',

            ],


            [
                'name' => 'خضار ببيلا',
                'phone' => '00963935971521',
                'password' => Hash::make('12345678'),
                'area_id' => 2,
                'type' => 'store',
                'status' => true,
                'open_hour' => '08:00:00',
                'close_hour' => '22:00:00',
                'image' => 'stores/خضار.jpg',
                'note' =>'ستجد لدينا كل كا تحتاج',

            ],
            [
                'name' => 'معجنات ببيلا',
                'phone' => '00963935971522',
                'password' => Hash::make('12345678'),
                'area_id' => 2,
                'type' => 'store',
                'status' => true,
                'open_hour' => '09:00:00',
                'close_hour' => '23:00:00',
                'image' => 'stores/معجنات.jpg',
                'note' =>'ستجد لدينا كل كا تحتاج',

            ],
            [
                'name' => 'مواد غذائية ببيلا',
                'phone' => '00963935971523',
                'password' => Hash::make('12345678'),
                'area_id' => 2,
                'type' => 'store',
                'status' => true,
                'open_hour' => '07:30:00',
                'close_hour' => '21:00:00',
                'image' => 'stores/غذائية.jpg',
                'note' =>'ستجد لدينا كل كا تحتاج',

            ],


            [
                'name' => 'خضار ركن الدين',
                'phone' => '00963935971531',
                'password' => Hash::make('12345678'),
                'area_id' => 3,
                'type' => 'store',
                'status' => true,
                'open_hour' => '08:00:00',
                'close_hour' => '22:00:00',
                'image' => 'stores/خضار.jpg',
                'note' =>'ستجد لدينا كل كا تحتاج',

            ],
            [
                'name' => 'معجنات ركن الدين',
                'phone' => '00963935971532',
                'password' => Hash::make('12345678'),
                'area_id' => 3,
                'type' => 'store',
                'status' => true,
                'open_hour' => '09:00:00',
                'close_hour' => '23:00:00',
                'image' => 'stores/معجنات.jpg',
                'note' =>'ستجد لدينا كل كا تحتاج',

            ],
            [
                'name' => 'مواد غذائية ركن الدين',
                'phone' => '00963935971533',
                'password' => Hash::make('12345678'),
                'area_id' => 3,
                'type' => 'store',
                'status' => true,
                'open_hour' => '07:30:00',
                'close_hour' => '21:00:00',
                'image' => 'stores/غذائية.jpg',
                'note' =>'ستجد لدينا كل كا تحتاج',

            ],
        ];

        foreach ($stores as $store) {
            User::create($store);
        }
    }
}

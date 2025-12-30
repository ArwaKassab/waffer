<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        // حذف المتاجر القديمة فقط (سيحذف المنتجات المرتبطة إذا FK عليها cascade)
        DB::table('users')->where('type', 'store')->delete();

        $note = 'ستجد لدينا كل ما تحتاج';

        $stores = [
            [
                'name' => 'خضار مزة',
                'user_name' =>'خضار مزة',
                'phone' => '00963935971511',
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
                'name' => 'معجنات مزة',
                'user_name' =>'معجنات مزة',

                'phone' => '00963935971512',
                'password' => Hash::make('12345678'),
                'area_id' => 1,
                'type' => 'store',
                'status' => true,
                'open_hour' => '09:00:00',
                'close_hour' => '23:00:00',
                'image' => 'stores/معجنات.jpg',
                'note'  => $note,
            ],
            [
                'name' => 'مواد غذائية مزة',
                'user_name' =>'مواد غذائية مزة',

                'phone' => '00963935971513',
                'password' => Hash::make('12345678'),
                'area_id' => 1,
                'type' => 'store',
                'status' => true,
                'open_hour' => '07:30:00',
                'close_hour' => '21:00:00',
                'image' => 'stores/غذائية.jpg',
                'note'  => $note,
            ],
            [
                'name' => 'خضار ببيلا',
                'user_name' =>'خضار ببيلا',
                'phone' => '00963935971521',
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
                'name' => 'معجنات ببيلا',
                'user_name' =>'معجنات ببيلا',
                'phone' => '00963935971522',
                'password' => Hash::make('12345678'),
                'area_id' => 2,
                'type' => 'store',
                'status' => true,
                'open_hour' => '09:00:00',
                'close_hour' => '23:00:00',
                'image' => 'stores/معجنات.jpg',
                'note'  => $note,
            ],
            [
                'name' => 'مواد غذائية ببيلا',
                'user_name' =>'مواد غذائية ببيلا',
                'phone' => '00963935971523',
                'password' => Hash::make('12345678'),
                'area_id' => 2,
                'type' => 'store',
                'status' => true,
                'open_hour' => '07:30:00',
                'close_hour' => '21:00:00',
                'image' => 'stores/غذائية.jpg',
                'note'  => $note,
            ],
            [
                'name' => 'خضار ركن الدين',
                'user_name' =>'خضار ركن الدين',
                'phone' => '00963935971531',
                'password' => Hash::make('12345678'),
                'area_id' => 3,
                'type' => 'store',
                'status' => true,
                'open_hour' => '08:00:00',
                'close_hour' => '22:00:00',
                'image' => 'stores/خضار.jpg',
                'note'  => $note,
            ],
            [
                'name' => 'معجنات ركن الدين',
                'user_name' =>'معجنات ركن الدين',
                'phone' => '00963935971532',
                'password' => Hash::make('12345678'),
                'area_id' => 3,
                'type' => 'store',
                'status' => true,
                'open_hour' => '09:00:00',
                'close_hour' => '23:00:00',
                'image' => 'stores/معجنات.jpg',
                'note'  => $note,
            ],
            [
                'name' => 'مواد غذائية ركن الدين',
                'user_name' => 'مواد غذائية ركن الدين',
                'phone' => '00963935971533',
                'password' => Hash::make('12345678'),
                'area_id' => 3,
                'type' => 'store',
                'status' => true,
                'open_hour' => '07:30:00',
                'close_hour' => '21:00:00',
                'image' => 'stores/غذائية.jpg',
                'note'  => $note,
            ],
        ];

        foreach ($stores as $store) {
            User::create($store);
        }
    }
}

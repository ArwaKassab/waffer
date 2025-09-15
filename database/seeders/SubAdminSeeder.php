<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class SubAdminSeeder extends Seeder
{
    public function run()
    {
        $subAdmins = [
            [
                'name' => 'Sub Admin area 1',
                'phone' => '00963935971599',
                'password' => Hash::make('password123'),
                'area_id' => 1,
                'type' => 'sub_admin',
                'status' => true,
            ],
            [
                'name' => 'Sub Admin area 2',
                'phone' => '00963945565414',
                'password' => Hash::make('password123'),
                'area_id' => 2,
                'type' => 'sub_admin',
                'status' => true,
            ],
            [
                'name' => 'Sub Admin area 3',
                'phone' => '00963935971511',
                'password' => Hash::make('password123'),
                'area_id' => 3,
                'type' => 'sub_admin',
                'status' => true,
            ],
        ];

        foreach ($subAdmins as $admin) {
            User::updateOrCreate(
                ['phone' => $admin['phone']], // شرط uniqueness
                $admin
            );
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class SubAdminSeeder extends Seeder
{
    public function run(): void
    {
        $subAdmins = [
            [
                'name' => 'Sub Admin area 1',
                'phone' => '00963935971591',
                'area_id' => 1,
            ],
            [
                'name' => 'Sub Admin area 2',
                'phone' => '00963935971592',
                'area_id' => 2,
            ],
            [
                'name' => 'Sub Admin area 3',
                'phone' => '00963935971593',
                'area_id' => 3,
            ],
        ];

        foreach ($subAdmins as $admin) {
            $user = User::firstOrNew([
                'phone' => $admin['phone'],
                'type'  => 'sub_admin',
            ]);

            $user->fill([
                'name'    => $admin['name'],
                'area_id' => $admin['area_id'],
                'status'  => true,
            ]);

            if (!$user->exists) {
                $user->password = Hash::make('password123');
            }

            $user->save();
        }
    }
}

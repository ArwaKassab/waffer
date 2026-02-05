<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        User::updateOrCreate(
            ['phone' => '00963935971524', 'type' => 'admin'], // رقم الهاتف الفريد لكل نوع
            [
                'name' => 'Super Admin',
                'user_name' => 'superadmin',
                'email' => 'superadmin@example.com',
                'password' => Hash::make('SuperPassword123!'), // كلمة المرور
                'status' => true,
            ]
        );

        // Sub Admin (مثال)
        User::updateOrCreate(
            ['phone' => '00963123456790', 'type' => 'sub_admin'],
            [
                'name' => 'Sub Admin',
                'user_name' => 'subadmin',
                'email' => 'subadmin@example.com',
                'password' => Hash::make('SubPassword123!'),
                'status' => true,
            ]
        );

        $this->command->info('Super Admin و Sub Admin تم إنشاؤهم بنجاح!');
    }
}

<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $this->call(areaSeeder::class);
        $this->call(StoreSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(StoreCategorySeeder::class);
        $this->call(SubAdminSeeder::class);
        $this->call(LinksTableSeeder::class);
        $this->call(AdsTableSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(Store2catSeeder::class);
        $this->call(Store2cat2Seeder::class);
        $this->call(ProductForTwoCatsSeeder::class);



    }
}

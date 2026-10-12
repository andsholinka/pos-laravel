<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with the demo store (deterministic).
     */
    public function run(): void
    {
        $this->call([
            StoreSettingSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            SalesHistorySeeder::class,
        ]);
    }
}

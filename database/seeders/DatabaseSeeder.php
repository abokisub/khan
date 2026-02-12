<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UnifiedBanksSeeder::class,
            SystemSettingsSeeder::class,
            AdminUserSeeder::class,
            FaqSeeder::class,
            SystemLocksSeeder::class,
            NetworkSeeder::class,
        ]);
    }
}

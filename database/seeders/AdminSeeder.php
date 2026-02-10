<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Check if admin already exists to prevent duplicates
        $admin = DB::table('user')->where('username', 'Habukhan')->first();

        if (!$admin) {
            DB::table('user')->insert([
                'name' => 'Habukhan Admin',
                'username' => 'Habukhan',
                'email' => 'admin@amtpay.com.ng',
                'phone' => '08000000000',
                'password' => Hash::make('@Habukhan2025'),
                'pin' => 1234,
                'type' => 'ADMIN', // Ensuring admin role
                'status' => 1, // Active
                'bal' => 1000000, // Starting balance for testing
                'refbal' => 0,
                'kyc' => 1, // Verified
                'date' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                // Add other necessary default fields based on schema if needed
                'apikey' => bin2hex(random_bytes(16)),
                'otp' => '123456',
            ]);
            $this->command->info('Admin account Habukhan created successfully.');
        } else {
            $this->command->info('Admin account Habukhan already exists.');
        }
    }
}

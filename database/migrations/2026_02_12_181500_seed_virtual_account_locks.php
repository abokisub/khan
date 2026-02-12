<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SeedVirtualAccountLocks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $locks = [
            ['provider' => 'xixapay', 'account_type' => 'palmpay', 'is_locked' => false, 'sort_order' => 1],
            ['provider' => 'xixapay', 'account_type' => 'kolomonie', 'is_locked' => false, 'sort_order' => 2],
            ['provider' => 'monnify', 'account_type' => 'moniepoint', 'is_locked' => false, 'sort_order' => 3],
            ['provider' => 'paystack', 'account_type' => 'wema', 'is_locked' => false, 'sort_order' => 4],
            ['provider' => 'paymentpoint', 'account_type' => 'palmpay', 'is_locked' => false, 'sort_order' => 5],
        ];

        foreach ($locks as $lock) {
            // Check if exists to avoid duplicates if migration runs multiple times or data exists
            $exists = DB::table('virtual_account_locks')
                ->where('provider', $lock['provider'])
                ->where('account_type', $lock['account_type'])
                ->exists();

            if (!$exists) {
                DB::table('virtual_account_locks')->insert([
                    'provider' => $lock['provider'],
                    'account_type' => $lock['account_type'],
                    'is_locked' => $lock['is_locked'],
                    'sort_order' => $lock['sort_order'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Optionally truncate or delete specific rows, but usually seeding isn't reversed strictly.
        // We can leave it or clear the table:
        // DB::table('virtual_account_locks')->truncate();
    }
}

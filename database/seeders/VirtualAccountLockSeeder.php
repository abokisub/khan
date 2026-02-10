<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VirtualAccountLockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $locks = [
            ['provider' => 'xixapay', 'account_type' => 'palmpay', 'is_locked' => false, 'sort_order' => 1],
            ['provider' => 'paymentpoint', 'account_type' => 'palmpay', 'is_locked' => false, 'sort_order' => 2],
            ['provider' => 'paystack', 'account_type' => 'wema', 'is_locked' => false, 'sort_order' => 3],
            ['provider' => 'monnify', 'account_type' => 'monniepoint', 'is_locked' => false, 'sort_order' => 4],
            ['provider' => 'xixapay', 'account_type' => 'kolomonie', 'is_locked' => false, 'sort_order' => 5],
        ];

        foreach ($locks as $lock) {
            DB::table('virtual_account_locks')->updateOrInsert(
                ['provider' => $lock['provider'], 'account_type' => $lock['account_type']],
                $lock
            );
        }
    }
}

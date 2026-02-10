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
            ['provider' => 'xixapay', 'account_type' => 'palmpay', 'is_locked' => false],
            ['provider' => 'xixapay', 'account_type' => 'kolomonie', 'is_locked' => false],
            ['provider' => 'monnify', 'account_type' => 'moniepoint', 'is_locked' => false],
            ['provider' => 'paystack', 'account_type' => 'wema', 'is_locked' => false],
            ['provider' => 'paymentpoint', 'account_type' => 'palmpay', 'is_locked' => false],
        ];

        foreach ($locks as $lock) {
            DB::table('virtual_account_locks')->updateOrInsert(
                ['provider' => $lock['provider'], 'account_type' => $lock['account_type']],
                $lock
            );
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Seed Settings Table
        DB::table('settings')->updateOrInsert(
            ['id' => 1],
            [
                'is_verify_email' => 0,
                'is_feature' => 1,
                'flutterwave' => 0,
                'monnify_atm' => 0,
                'monnify' => 1,
                'wema' => 1,
                'fed' => 1,
                'str' => 1,
                'earning' => 1,
                'referral' => 1,
                'kolomoni_mfb' => 1,
                'bulksms' => 1,
                'allow_pin' => 1,
                'bill' => 1,
                'bank_transfer' => 1,
                'paystack' => 1,
                'allow_limit' => 1,
                'stock' => 1,
                'card_ngn_lock' => 0,
                'card_usd_lock' => 0,
                'monnify_charge' => 50.00,
                'xixapay_charge' => 50.00,
                'paystack_charge' => 50.00,
                'transfer_charge_type' => 'FLAT',
                'transfer_charge_value' => 20.00,
                'transfer_charge_cap' => 0.00,
                'transfer_lock_all' => 0,
                'primary_transfer_provider' => 'xixapay',
                'default_virtual_account' => 'palmpay',
                'palmpay_enabled' => true,
                'monnify_enabled' => true,
                'wema_enabled' => true,
                'xixapay_enabled' => true,
                'referral_price' => 100.00,
                'version' => '1.0.0',
                'maintenance' => false,
                'notif_show' => true,
                'notif_message' => 'Welcome to Habukhan! Your trusted partner for seamless transactions.',
                'ads_show' => true,
                'ads_message' => 'Check out our new referral program and earn more!',
                'app_notif_show' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        );

        // Seed General Table
        DB::table('general')->updateOrInsert(
            ['id' => 1],
            [
                'app_name' => 'Habukhan',
                'app_email' => 'support@habukhan.com',
                'app_phone' => '+2348139123922',
                'app_address' => 'Nigeria',
                'currency' => 'NGN',
                'currency_symbol' => '₦',
                'timezone' => 'Africa/Lagos',
                'maintenance_mode' => false,
                'facebook' => 'https://facebook.com/habukhan',
                'instagram' => 'https://instagram.com/habukhan',
                'tiktok' => 'https://tiktok.com/@habukhan',
                'play_store_url' => 'https://play.google.com/store/apps/details?id=com.habukhan.mobile',
                'app_store_url' => 'https://apps.apple.com/app/habukhan',
            ]
        );

        // Seed Habukhan Key (formerly Adex Key)
        DB::table('habukhan_key')->updateOrInsert(
            ['id' => 1],
            [
                'account_number' => '1234567890',
                'account_name' => 'Habukhan Admin',
                'bank_name' => 'Al-Barakah Microfinance Bank',
                'min' => 100.00,
                'max' => 1000000.00,
                'mon_app_key' => 'MK_TEST_XXXXXXXXXX',
                'mon_sk_key' => 'MSK_TEST_XXXXXXXXXX',
                'mon_con_num' => '1234567890',
                'mon_bvn' => '22222222222',
                'psk' => 'sk_test_XXXXXXXXXX',
                'psk_bvn' => '22222222222',
                'plive' => false,
            ]
        );

        // Seed Card Settings
        DB::table('card_settings')->updateOrInsert(
            ['id' => 1],
            [
                'ngn_creation_fee' => 500.00,
                'usd_creation_fee' => 3.00,
                'ngn_rate' => 1650.00,
                'funding_fee_percent' => 1.00,
                'usd_failed_tx_fee' => 0.40,
                'ngn_funding_fee_percent' => 2.00,
                'usd_funding_fee_percent' => 2.00,
                'ngn_failed_tx_fee' => 0.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        );

        // Seed Feature List
        $features = [
            ['name' => 'Data Purchase', 'status' => 1],
            ['name' => 'Airtime Topup', 'status' => 1],
            ['name' => 'Cable TV', 'status' => 1],
            ['name' => 'Electricity Bill', 'status' => 1],
            ['name' => 'Internal Transfer', 'status' => 1],
            ['name' => 'Bank Transfer', 'status' => 1],
            ['name' => 'Virtual Card', 'status' => 1],
        ];

        foreach ($features as $feature) {
            DB::table('feature')->updateOrInsert(
                ['name' => $feature['name']],
                ['status' => $feature['status']]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NetworkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $networks = [
            [
                'network' => 'MTN',
                'plan_id' => 'mtn_network_001',
                'network_vtu' => true,
                'network_share' => true,
                'network_sme' => true,
                'network_cg' => true,
                'network_g' => true,
                'cash' => true,
                'data_card' => true,
                'recharge_card' => true,
            ],
            [
                'network' => 'GLO',
                'plan_id' => 'glo_network_002',
                'network_vtu' => true,
                'network_share' => true,
                'network_sme' => true,
                'network_cg' => true,
                'network_g' => true,
                'cash' => true,
                'data_card' => true,
                'recharge_card' => true,
            ],
            [
                'network' => 'AIRTEL',
                'plan_id' => 'airtel_network_003',
                'network_vtu' => true,
                'network_share' => true,
                'network_sme' => true,
                'network_cg' => true,
                'network_g' => true,
                'cash' => true,
                'data_card' => true,
                'recharge_card' => true,
            ],
            [
                'network' => '9MOBILE',
                'plan_id' => '9mobile_network_004',
                'network_vtu' => true,
                'network_share' => true,
                'network_sme' => true,
                'network_cg' => true,
                'network_g' => true,
                'cash' => true,
                'data_card' => true,
                'recharge_card' => true,
            ],
        ];

        foreach ($networks as $network) {
            DB::table('network')->updateOrInsert(
                ['network' => $network['network']],
                $network
            );
        }
    }
}

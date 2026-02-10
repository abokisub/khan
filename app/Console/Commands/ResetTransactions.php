<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:reset {--confirm : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset all transactions and user balances to zero';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Ask for confirmation unless --confirm flag is used
        if (!$this->option('confirm')) {
            if (!$this->confirm('⚠️  WARNING: This will delete ALL transactions and reset ALL user balances to 0. Are you sure?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Starting transaction reset...');

        try {
            // Truncate all transaction tables
            $tables = [
                'airtime',
                'data',
                'cable',
                'bill',
                'exam',
                'bulksms',
                'cash',
                'deposit',
                'bank_transfer',
                'data_card',
                'recharge_card',
            ];

            foreach ($tables as $table) {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    DB::table($table)->truncate();
                    $this->info("✓ Cleared {$table} table");
                }
            }

            // Reset all user balances
            DB::table('user')->update([
                'bal' => 0.00,
                'refbal' => 0.00,
            ]);
            $this->info('✓ Reset all user balances to 0.00');

            $this->newLine();
            $this->info('✅ All transactions deleted and user balances reset successfully!');

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}

<?php
// Quick test script to check network table structure
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== NETWORK TABLE STRUCTURE ===\n\n";

// Get all columns in the network table
$columns = Schema::getColumnListing('network');
echo "Columns in 'network' table:\n";
foreach ($columns as $column) {
    echo "  - $column\n";
}

echo "\n=== CHECKING FOR REQUIRED COLUMNS ===\n\n";
$requiredColumns = ['network_sme', 'network_cg', 'network_g', 'network_sme2', 'network_datashare'];
foreach ($requiredColumns as $col) {
    $exists = Schema::hasColumn('network', $col);
    $status = $exists ? '✓ EXISTS' : '✗ MISSING';
    echo "$status: $col\n";
}

echo "\n=== CURRENT NETWORK DATA ===\n\n";
$networks = DB::table('network')->get();
foreach ($networks as $network) {
    echo "Network: {$network->network}\n";
    echo "  SME: " . ($network->network_sme ?? 'NULL') . "\n";
    echo "  CG: " . ($network->network_cg ?? 'NULL') . "\n";
    echo "  G: " . ($network->network_g ?? 'NULL') . "\n";
    echo "  SME2: " . ($network->network_sme2 ?? 'NULL') . "\n";
    echo "  DATASHARE: " . ($network->network_datashare ?? 'NULL') . "\n";
    echo "\n";
}

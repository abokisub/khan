<?php

use Illuminate\Support\Facades\Artisan;

// Load Laravel Bootstrap
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<h1>Laravel Web Migrator</h1>";

try {
    echo "<p>Running migrations...</p>";

    // This is equivalent to running 'php artisan migrate' in the terminal
    $exitCode = Artisan::call('migrate', [
        '--force' => true, // Required for production
    ]);

    echo "<pre>" . Artisan::output() . "</pre>";

    if ($exitCode === 0) {
        echo "<p style='color: green;'><strong>Success!</strong> Your database is now up to date.</p>";
        echo "<p>Please <strong>DELETE this file</strong> immediately for security.</p>";
    }
    else {
        echo "<p style='color: red;'>Migration failed with exit code: $exitCode</p>";
    }

}
catch (\Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
}
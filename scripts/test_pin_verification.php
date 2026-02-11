<?php
require 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

// Mock Laravel app
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

class PinTester extends Controller
{
    public function testVerify($userId, $sentPin)
    {
        // Simulating the logic in AirtimePurchase.php / DataPurchase.php
        $check = DB::table('user')->where(['id' => $userId, 'status' => 1]);
        if ($check->count() == 1) {
            $user = $check->first();
            $storedPin = $user->pin;
            $match = (trim($storedPin) == trim($sentPin));

            echo "User ID: $userId\n";
            echo "Stored PIN: [" . ($storedPin ?? 'NULL') . "] (Type: " . gettype($storedPin) . ")\n";
            echo "Sent PIN: [" . ($sentPin ?? 'NULL') . "] (Type: " . gettype($sentPin) . ")\n";
            echo "Match Result: " . ($match ? "TRUE" : "FALSE") . "\n";

            if (!$match) {
                echo "DEBUG: Stored Trimmed: [" . trim($storedPin) . "]\n";
                echo "DEBUG: Sent Trimmed: [" . trim($sentPin) . "]\n";
            }
        } else {
            echo "User not found or inactive.\n";
        }
    }
}

$tester = new PinTester();
$userId = 1; // Change to a valid user ID for testing
$pin = '1234'; // Change to the expected PIN

echo "--- Testing PIN Verification ---\n";
$tester->testVerify($userId, $pin);

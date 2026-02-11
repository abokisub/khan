<?php

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require_once dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\API\PaymentPointWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$testUserEmail = 'abokisub@gmail.com';
$testAccountNumber = '6615406825';

echo "\n--- TESTING CROSS-PROVIDER CHARGE LOGIC ---\n";
echo "Scenario: Xixapay webhook hits PaymentPoint endpoint\n";

// Use Xixapay Payload structure
$payload = [
    'notification_status' => 'payment_successful',
    'transaction_status' => 'success', // Add this as PaymentPoint controller checks for it
    'amount_paid' => 100,
    'settlement_amount' => 100,
    'settlement_fee' => 0,
    'transaction_id' => 'CROSS_TEST_' . time(),
    'receiver' => ['account_number' => $testAccountNumber],
    'customer' => ['email' => $testUserEmail]
];

// Sign with XIXAPAY secret
$xixapaySecret = trim(config('services.xixapay.secret_key'));
$signature = hash_hmac('sha256', json_encode($payload), $xixapaySecret);

$request = Request::create('/api/webhook/paymentpoint', 'POST', [], [], [], [
    'HTTP_CONTENT_TYPE' => 'application/json',
    'HTTP_XIXAPAY' => $signature
], json_encode($payload));

try {
    $oldBal = DB::table('user')->where('email', $testUserEmail)->value('bal');
    echo "Initial Balance: $oldBal\n";

    $xixaCharge = DB::table('settings')->value('xixapay_charge');
    $ppCharge = DB::table('settings')->value('paymentpoint_charge');
    echo "Settings -> Xixapay Charge: $xixaCharge, PP Charge: $ppCharge\n";

    $controller = $app->make(PaymentPointWebhookController::class);
    $response = $controller->handleWebhook($request);

    echo "HTTP Status: " . $response->getStatusCode() . "\n";

    $newBal = DB::table('user')->where('email', $testUserEmail)->value('bal');
    echo "Final Balance: $newBal\n";

    $diff = $newBal - $oldBal;
    echo "Credit Received: $diff\n";

    $expected = 100 - $xixaCharge;
    echo "Expected Credit (100 - $xixaCharge): $expected\n";

    if (abs($diff - $expected) < 0.01) {
        echo "SUCCESS: Correct charge applied!\n";
    } else {
        echo "FAILURE: Incorrect charge applied.\n";
    }

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

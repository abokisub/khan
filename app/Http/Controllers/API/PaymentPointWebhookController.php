<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\PaymentPointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentPointWebhookController extends Controller
{
    private $paymentPointService;

    public function __construct(PaymentPointService $paymentPointService)
    {
        $this->paymentPointService = $paymentPointService;
    }

    /**
     * Handle PaymentPoint webhook notifications
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleWebhook(Request $request)
    {
        try {
            // Get raw payload
            $payload = $request->getContent();
            $signature = $request->header('Paymentpoint-Signature');

            // Verify signature
            if (!$this->paymentPointService->verifyWebhookSignature($payload, $signature)) {
                Log::warning('PaymentPoint webhook signature verification failed', [
                    'signature' => $signature,
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Decode payload
            $data = json_decode($payload, true);

            if (!$data) {
                return response()->json(['error' => 'Invalid JSON'], 400);
            }

            // Log webhook received
            Log::info('PaymentPoint webhook received', $data);

            // Process payment if successful
            if ($data['notification_status'] === 'payment_successful' && $data['transaction_status'] === 'success') {
                $this->processPayment($data);
            }

            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            Log::error('PaymentPoint webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Process successful payment
     *
     * @param array $data
     * @return void
     */
    private function processPayment($data)
    {
        try {
            $accountNumber = $data['receiver']['account_number'];
            $amountPaid = $data['amount_paid'];
            $settlementAmount = $data['settlement_amount'];
            $settlementFee = $data['settlement_fee'];
            $transactionId = $data['transaction_id'];
            $customerEmail = $data['customer']['email'] ?? null;

            // Find user by PaymentPoint account number
            $user = DB::table('user')
                ->where('paymentpoint_account_number', $accountNumber)
                ->first();

            if (!$user) {
                Log::warning('PaymentPoint webhook: User not found', [
                    'account_number' => $accountNumber,
                ]);
                return;
            }

            // Get PaymentPoint charge from settings
            $charge = DB::table('card_settings')
                ->where('name', 'paymentpoint_charge')
                ->value('amount') ?? 0;

            // Calculate final amount after charge
            $finalAmount = $settlementAmount - $charge;

            if ($finalAmount <= 0) {
                Log::warning('PaymentPoint webhook: Amount too low after charges', [
                    'settlement_amount' => $settlementAmount,
                    'charge' => $charge,
                ]);
                return;
            }

            // Get old balance
            $oldBalance = $user->bal;
            $newBalance = $oldBalance + $finalAmount;

            // Update user balance
            DB::table('user')
                ->where('id', $user->id)
                ->update(['bal' => $newBalance]);

            // Record deposit transaction
            DB::table('deposit')->insert([
                'username' => $user->username,
                'amount' => $amountPaid,
                'oldbal' => $oldBalance,
                'newbal' => $newBalance,
                'charges' => $charge + $settlementFee,
                'payment_method' => 'PaymentPoint',
                'reference' => $transactionId,
                'transid' => $transactionId,
                'wallet_type' => 'main',
                'type' => 'credit',
                'status' => 1,
                'date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('PaymentPoint payment processed successfully', [
                'user_id' => $user->id,
                'username' => $user->username,
                'amount' => $finalAmount,
                'transaction_id' => $transactionId,
            ]);
        } catch (\Exception $e) {
            Log::error('PaymentPoint payment processing error', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
        }
    }
}

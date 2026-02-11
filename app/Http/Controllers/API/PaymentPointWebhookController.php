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

            Log::info('PaymentPoint Webhook Headers', $request->headers->all());

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
            $notificationStatus = $data['notification_status'] ?? null;
            $transactionStatus = $data['transaction_status'] ?? null;

            if ($notificationStatus === 'payment_successful' && $transactionStatus === 'success') {
                $this->processPayment($data);
            } else {
                Log::info('PaymentPoint webhook: Skipping non-successful notification', [
                    'notification_status' => $notificationStatus,
                    'transaction_status' => $transactionStatus
                ]);
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
            $accountNumber = $data['receiver']['account_number'] ?? null;
            $amountPaid = floatval($data['amount_paid'] ?? 0);
            $settlementAmount = floatval($data['settlement_amount'] ?? 0);
            $settlementFee = floatval($data['settlement_fee'] ?? 0);
            $transactionId = $data['transaction_id'] ?? null;
            $customerEmail = $data['customer']['email'] ?? null;

            if (!$accountNumber && !$customerEmail) {
                Log::warning('PaymentPoint webhook: Missing receiver account or customer email');
                return;
            }

            // Find user by PaymentPoint account number OR email (fallback)
            $user = DB::table('user')
                ->where('paymentpoint_account_number', $accountNumber)
                ->orWhere('email', $customerEmail)
                ->first();

            if (!$user) {
                Log::warning('PaymentPoint webhook: User not found', [
                    'account_number' => $accountNumber,
                ]);
                return;
            }

            // Get PaymentPoint charge from settings
            $charge = $this->core()->paymentpoint_charge ?? 0;

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

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentPointService
{
    private $apiKey;
    private $apiSecret;
    private $businessId;
    private $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('PAYMENTPOINT_API_KEY');
        $this->apiSecret = env('PAYMENTPOINT_API_SECRET');
        $this->businessId = env('PAYMENTPOINT_BUSINESS_ID');
        $this->baseUrl = 'https://api.paymentpoint.co';
    }

    /**
     * Create a virtual account for a user
     *
     * @param object $user
     * @return array|null
     */
    public function createVirtualAccount($user)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiSecret,
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'User-Agent' => 'AmtPay/1.0',
            ])->timeout(30)->withOptions(['verify' => false])->post($this->baseUrl . '/api/v1/createVirtualAccount', [
                        'email' => $user->email,
                        'name' => $user->name,
                        'phoneNumber' => $user->phone,
                        'bankCode' => ['20946'], // Palmpay only
                        'businessId' => $this->businessId,
                    ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'success' && !empty($data['bankAccounts'])) {
                    $account = $data['bankAccounts'][0];

                    // Store in user table
                    $user->update([
                        'paymentpoint_account_number' => $account['accountNumber'],
                        'paymentpoint_account_name' => $account['accountName'],
                        'paymentpoint_bank_name' => $account['bankName'],
                        'paymentpoint_customer_id' => $data['customer']['customer_id'],
                        'paymentpoint_reserved_id' => $account['Reserved_Account_Id'],
                    ]);

                    Log::info('PaymentPoint virtual account created', [
                        'user_id' => $user->id,
                        'account_number' => $account['accountNumber'],
                    ]);

                    return $account;
                }
            }

            Log::error('PaymentPoint account creation failed', [
                'user_id' => $user->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('PaymentPoint API error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Verify webhook signature
     *
     * @param string $payload
     * @param string $signature
     * @return bool
     */
    public function verifyWebhookSignature($payload, $signature)
    {
        // Extract the secret key from the Bearer token (remove "Bearer " prefix)
        $secretKey = str_replace('Bearer ', '', $this->apiSecret);

        $calculatedSignature = hash_hmac('sha256', $payload, $secretKey);

        return hash_equals($calculatedSignature, $signature);
    }
}

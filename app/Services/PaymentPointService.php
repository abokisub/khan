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
        $this->apiKey = config('services.paymentpoint.api_key');
        $this->apiSecret = config('services.paymentpoint.api_secret');
        $this->businessId = config('services.paymentpoint.business_id');
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
        $bodyData = [
            'email' => $user->email,
            'name' => $user->name,
            'phoneNumber' => $user->phone,
            'bankCode' => ['20946', '20897'], // Palmpay and OPay
            'businessId' => $this->businessId,
        ];

        try {
            $url = $this->baseUrl . '/api/v1/createVirtualAccount';

            // Ensure no double Bearer prefix and clean formatting
            $apiSecret = trim($this->apiSecret);
            if (str_starts_with($apiSecret, 'Bearer ')) {
                $apiSecret = substr($apiSecret, 7);
            }

            // check if keys are loaded
            if (empty($apiSecret) || empty($this->apiKey)) {
                Log::error('PaymentPoint Keys Missing', [
                    'user_id' => $user->id,
                    'key_exists' => !empty($this->apiKey),
                    'secret_exists' => !empty($apiSecret)
                ]);
                return null;
            }

            $response = Http::withHeaders([
                'api-key' => trim($this->apiKey),
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Expect' => '',
            ])
                ->withToken($apiSecret)
                ->timeout(30)
                ->post($url, $bodyData);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['status']) && $data['status'] === 'success' && !empty($data['bankAccounts'])) {
                    $account = $data['bankAccounts'][0];

                    // Store in user table
                    $user->update([
                        'paymentpoint_account_number' => $account['accountNumber'],
                        'paymentpoint_account_name' => $account['accountName'],
                        'paymentpoint_bank_name' => $account['bankName'],
                        'paymentpoint_customer_id' => $data['customer']['customer_id'],
                        'paymentpoint_reserved_id' => $account['Reserved_Account_Id'],
                    ]);

                    Log::info('PaymentPoint virtual account created (HTTP)', [
                        'user_id' => $user->id,
                        'account_number' => $account['accountNumber'],
                    ]);

                    return $account;
                } else {
                    Log::error('PaymentPoint API returned error', [
                        'user_id' => $user->id,
                        'response' => $data,
                        'status' => $response->status()
                    ]);
                    return null;
                }
            } else {
                Log::error('PaymentPoint HTTP Request Failed', [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

        } catch (\Exception $e) {
            Log::error('PaymentPoint Exception', [
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

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
        $bodyData = [
            'email' => $user->email,
            'name' => $user->name,
            'phoneNumber' => $user->phone,
            'bankCode' => ['20946', '20897'], // Palmpay and OPay
            'businessId' => $this->businessId,
        ];

        // Use temp file to avoid command line argument length/escaping issues
        $tempFile = tempnam(sys_get_temp_dir(), 'pp_req_');
        file_put_contents($tempFile, json_encode($bodyData));

        try {
            $url = $this->baseUrl . '/api/v1/createVirtualAccount';

            // Ensure no double Bearer prefix and clean formatting
            $apiSecret = trim($this->apiSecret);
            if (str_starts_with($apiSecret, 'Bearer ')) {
                $apiSecret = substr($apiSecret, 7);
            }

            $authHeader = 'Authorization: Bearer ' . $apiSecret;
            $keyHeader = 'api-key: ' . trim($this->apiKey);

            // Using --http1.1 and a common Browser User-Agent to avoid hangs/blocks
            // Also disabling Expect header to avoid 100-continue issues
            // Adding timeouts to prevent long-running hangs
            $cmd = "/usr/bin/curl -s --http1.1 --connect-timeout 10 --max-time 30 -X POST " . escapeshellarg($url) .
                " -H 'Content-Type: application/json'" .
                " -H " . escapeshellarg($authHeader) .
                " -H " . escapeshellarg($keyHeader) .
                " -H 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'" .
                " -H 'Expect:'" .
                " -H 'Connection: close'" .
                " -d @" . escapeshellarg($tempFile) .
                " 2>&1"; // Capture stderr

            $output = shell_exec($cmd);

            // Clean up
            @unlink($tempFile);

            $data = json_decode($output, true);

            if ($data && isset($data['status']) && $data['status'] === 'success' && !empty($data['bankAccounts'])) {
                $account = $data['bankAccounts'][0];

                // Store in user table
                $user->update([
                    'paymentpoint_account_number' => $account['accountNumber'],
                    'paymentpoint_account_name' => $account['accountName'],
                    'paymentpoint_bank_name' => $account['bankName'],
                    'paymentpoint_customer_id' => $data['customer']['customer_id'],
                    'paymentpoint_reserved_id' => $account['Reserved_Account_Id'],
                ]);

                Log::info('PaymentPoint virtual account created (CLI)', [
                    'user_id' => $user->id,
                    'account_number' => $account['accountNumber'],
                ]);

                return $account;
            } else {
                Log::error('PaymentPoint account creation failed (CLI)', [
                    'user_id' => $user->id,
                    'output' => $output,
                ]);
                return null;
            }
        } catch (\Exception $e) {
            @unlink($tempFile);
            Log::error('PaymentPoint CLI error', [
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

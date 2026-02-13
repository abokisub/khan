<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Beneficiary;
use App\Services\ReceiptService;


class BillPurchase extends Controller
{
    public function Buy(Request $request)
    {
        $auth = $this->resolveAuthContext($request);
        if ($auth instanceof \Illuminate\Http\JsonResponse) {
            return $auth;
        }

        $user = $this->resolveUser($auth['accessToken']);
        if (!$user) {
            return response()->json(['status' => 'fail', 'message' => 'Invalid Access Token'])->setStatusCode(403);
        }

        $validator = $this->getBillValidator($request, $auth['type']);
        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'message' => $validator->errors()->first()])->setStatusCode(403);
        }

        return $this->processBillPayment($request, $user, $auth['transid'], $auth['system']);
    }

    private function resolveAuthContext(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));

        if (config('app.habukhan_device_key') == $request->header('Authorization')) {
            $verified_id = $this->verifyapptoken($request->user_id);
            $user = DB::table('user')->where(['id' => $verified_id, 'status' => 1])->first();

            if (!$user) {
                return ['accessToken' => 'null', 'transid' => $this->purchase_ref('BILL_'), 'system' => 'APP', 'type' => 'APP'];
            }

            if ($this->core()->allow_pin == 1 && trim($user->pin) != trim($request->pin)) {
                Log::warning("Bill PIN Validation Failed for user: {$user->username}. Sent: [" . ($request->pin ?? 'NULL') . "], Stored: [" . ($user->pin ?? 'NULL') . "]");
                return response()->json(['status' => 'fail', 'message' => 'Invalid Transaction Pin'])->setStatusCode(403);
            }

            return [
                'accessToken' => $user->apikey,
                'transid' => $request->input('request-id') ?? $this->purchase_ref('BILL_'),
                'system' => 'APP',
                'type' => 'APP'
            ];
        }

        if (!$request->headers->get('origin') || in_array($request->headers->get('origin'), $explode_url)) {
            $user_id = $this->verifytoken($request->token);
            $user = DB::table('user')->where(['id' => $user_id])->first();

            if (!$user) {
                return response()->json(['status' => 'fail', 'message' => 'An Error Occur'])->setStatusCode(403);
            }

            if ($this->core()->allow_pin == 1 && trim($user->pin) != trim($request->pin)) {
                return response()->json(['status' => 'fail', 'message' => 'Invalid Transaction Pin'])->setStatusCode(403);
            }

            return [
                'accessToken' => $user->apikey,
                'transid' => $this->purchase_ref('BILL_'),
                'system' => config('app.name'),
                'type' => 'WEB'
            ];
        }

        // Default: API
        $authHeader = $request->header('Authorization');
        if (strpos($authHeader, 'Token ') === 0) {
            $authHeader = substr($authHeader, 6);
        }

        return [
            'accessToken' => trim($authHeader),
            'transid' => $request->input('request-id'),
            'system' => 'API',
            'type' => 'API'
        ];
    }

    private function resolveUser($accessToken)
    {
        return DB::table('user')->where(function ($query) use ($accessToken) {
            $query->where('apikey', $accessToken)
                ->orWhere('app_key', $accessToken)
                ->orWhere('habukhan_key', $accessToken);
        })->where('status', 1)->first();
    }

    private function getBillValidator(Request $request, $type)
    {
        $rules = [
            'disco' => 'required',
            'meter_number' => 'required',
            'bypass' => 'required',
            'meter_type' => 'required',
            'amount' => 'required|numeric|integer|not_in:0|gt:0',
        ];

        if ($type === 'APP') {
            $rules['user_id'] = 'required';
        } elseif ($type === 'API') {
            $rules['request-id'] = 'required|unique:bill,transid';
        }

        return Validator::make($request->all(), $rules);
    }

    private function processBillPayment(Request $request, $user, $transid, $system)
    {
        if (DB::table('block')->where(['number' => $request->meter_number])->count() > 0) {
            return response()->json(['status' => 'fail', 'message' => 'Number Block'])->setStatusCode(403);
        }

        if (DB::table('bill')->where('transid', $transid)->count() > 0 || DB::table('message')->where('transid', $transid)->count() > 0) {
            return response()->json(['status' => 'fail', 'message' => 'Referrence ID Used'])->setStatusCode(403);
        }

        $bill_plan = DB::table('bill_plan')->where(['plan_id' => $request->disco, 'plan_status' => 1])->first();
        if (!$bill_plan) {
            return response()->json(['status' => 'fail', 'message' => 'Invalid Disco ID'])->setStatusCode(403);
        }

        if ($this->core()->bill != 1) {
            return response()->json(['status' => 'fail', 'message' => 'Electricity Bill Not Available Right Now'])->setStatusCode(403);
        }

        DB::beginTransaction();
        $user = DB::table('user')->where(['id' => $user->id])->lockForUpdate()->first();

        if (!is_numeric($user->bal) || $user->bal <= 0) {
            return response()->json(['status' => 'fail', 'message' => 'Insufficient Account Kindly Fund Your Wallet'])->setStatusCode(403);
        }

        $bill_d = DB::table('bill_charge')->first();
        if ($request->amount < $bill_d->bill_min || $request->amount > $bill_d->bill_max) {
            $limitType = ($request->amount < $bill_d->bill_min) ? 'Minimum' : 'Maximum';
            $limitValue = ($request->amount < $bill_d->bill_min) ? $bill_d->bill_min : $bill_d->bill_max;
            return response()->json(['status' => 'fail', 'message' => "{$limitType} Electricity Purchase is ₦" . number_format($limitValue, 2)])->setStatusCode(403);
        }

        $charges = ($bill_d->direct == 1) ? $bill_d->bill : ($request->amount / 100) * $bill_d->bill;
        $total_amount = $charges + $request->amount;

        if ($user->bal < $total_amount) {
            return response()->json(['status' => 'fail', 'message' => 'Insufficient Account Kindly Fund Your Wallet'])->setStatusCode(403);
        }

        // Validate meter
        $bill_sel = DB::table('bill_sel')->first();
        $adm = new MeterSend();
        $check_now = $bill_sel->bill;
        $sending_data = [
            'disco' => $request->disco,
            'meter_type' => strtolower($request->meter_type),
            'meter_number' => strtolower($request->meter_number)
        ];

        if (method_exists($adm, $check_now)) {
            $customer_name = $adm->$check_now($sending_data);
        } else {
            Log::error("BillPurchase Error: Method {$check_now} does not exist in MeterSend.");
            $customer_name = null;
        }

        if (empty($customer_name) && ($request->bypass == false || $request->bypass == 'false')) {
            return response()->json(['status' => 'fail', 'message' => 'Invalid Meter Number'])->setStatusCode(403);
        }

        // Debit
        $debit = $user->bal - $total_amount;
        if (!DB::table('user')->where(['id' => $user->id])->update(['bal' => $debit])) {
            return response()->json(['status' => 'fail', 'message' => 'unable to debit user'])->setStatusCode(403);
        }

        DB::commit();

        return $this->finalizeBillTransaction($request, $user, $bill_plan, $transid, $charges, $total_amount, $debit, $customer_name, $system, $check_now);
    }

    private function finalizeBillTransaction($request, $user, $bill_plan, $transid, $charges, $total_amount, $debit, $customer_name, $system, $check_now)
    {
        $trans_history = [
            'username' => $user->username,
            'amount' => $total_amount,
            'message' => "⏳ Processing " . strtoupper($bill_plan->disco_name) . " " . strtoupper($request->meter_type) . " ₦" . $request->amount . " to " . $request->meter_number . "...",
            'phone_account' => $request->meter_number,
            'oldbal' => $user->bal,
            'newbal' => $debit,
            'habukhan_date' => $this->system_date(),
            'plan_status' => 0,
            'transid' => $transid,
            'role' => 'bill',
            'service_type' => 'ELECTRICITY',
            'transaction_channel' => 'EXTERNAL'
        ];

        $bill_trans = [
            'username' => $user->username,
            'amount' => $request->amount,
            'disco_name' => $bill_plan->disco_name,
            'meter_number' => $request->meter_number,
            'meter_type' => strtoupper($request->meter_type),
            'charges' => $charges,
            'newbal' => $debit,
            'oldbal' => $user->bal,
            'customer_name' => $customer_name,
            'system' => $system,
            'plan_status' => 0,
            'plan_date' => $this->system_date(),
            'transid' => $transid
        ];

        if (!$this->inserting_data('message', $trans_history) || !$this->inserting_data('bill', $bill_trans)) {
            DB::table('user')->where(['username' => $user->username, 'id' => $user->id])->update(['bal' => $user->bal]); // Refund attempt
            return response()->json(['status' => 'fail', 'message' => 'Unable to insert'])->setStatusCode(403);
        }

        $billvend = new BillSend();
        $bill_data = [
            'username' => $user->username,
            'plan_id' => $request->disco,
            'transid' => $transid
        ];

        $response = $billvend->$check_now($bill_data);

        return $this->handleProviderResponse($request, $user, $bill_plan, $transid, $charges, $debit, $response, $system);
    }

    private function handleProviderResponse($request, $user, $bill_plan, $transid, $charges, $debit, $response, $system)
    {
        $receiptService = new ReceiptService();

        if ($response == 'success') {
            try {
                Beneficiary::updateOrCreate(
                    ['user_id' => $user->id, 'service_type' => 'electricity', 'identifier' => $request->meter_number],
                    ['network_or_provider' => $bill_plan->disco_name, 'last_used_at' => Carbon::now()]
                );
            } catch (\Exception $e) {
                Log::error('Electricity Beneficiary Save Failed: ' . $e->getMessage());
            }

            $habukhan_forgot = DB::table('bill')->where('transid', $transid)->first();
            $successMessage = $receiptService->getFullMessage('ELECTRICITY', [
                'meter_no' => $request->meter_number,
                'token' => $habukhan_forgot->token ?? 'Processing',
                'amount' => $request->amount,
                'reference' => $transid,
                'status' => 'SUCCESS',
                'provider' => $bill_plan->disco_name
            ]);

            DB::table('message')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 1, 'message' => $successMessage]);
            DB::table('bill')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 1]);

            try {
                (new \App\Services\NotificationService())->sendBillNotification($user, $request->amount, $bill_plan->disco_name, $request->meter_number, $habukhan_forgot->token ?? null, $transid);
            } catch (\Exception $e) {
                Log::error("Bill Notification Error: " . $e->getMessage());
            }

            if (isset($request->is_api) && $request->is_api == true) {
                return response()->json(['status' => 'success', 'message' => 'Transaction Successful', 'transid' => $transid, 'token' => $habukhan_forgot->token ?? null]);
            }

            return response()->json([
                'disco_name' => strtoupper($bill_plan->disco_name),
                'request-id' => $transid,
                'amount' => $request->amount,
                'charges' => $charges,
                'transid' => $transid,
                'status' => 'success',
                'message' => 'Transaction successful ' . strtoupper($bill_plan->disco_name) . ' ' . strtoupper($request->meter_type) . ' ₦' . $request->amount . ' to ' . $request->meter_number,
                'meter_number' => $request->meter_number,
                'meter_type' => strtoupper($request->meter_type),
                'oldbal' => $user->bal,
                'newbal' => $debit,
                'system' => $system,
                'token' => $habukhan_forgot->token ?? null,
                'wallet_vending' => 'wallet',
            ]);
        }

        if ($response == 'fail') {
            $refund = $user->bal; // user->bal was initial bal, debit was balance after debit.
            // Wait, in my refactored processBillPayment, I committed the transaction.
            // So I need to refund the original amount + charges.
            $bill_d = DB::table('bill_charge')->first();
            $charges = ($bill_d->direct == 1) ? $bill_d->bill : ($request->amount / 100) * $bill_d->bill;
            $refundAmount = $request->amount + $charges;
            $actualRefund = $debit + $refundAmount;

            DB::table('user')->where(['username' => $user->username, 'id' => $user->id])->update(['bal' => $actualRefund]);

            $failMessage = "❌ Electricity Payment Failed\n\nYou attempted to pay ₦" . $request->amount . " for meter " . $request->meter_number . " (" . strtoupper($bill_plan->disco_name) . ") but the transaction failed. Your wallet has been refunded.";
            DB::table('message')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 2, 'newbal' => $actualRefund, 'message' => $failMessage]);
            DB::table('bill')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 2, 'newbal' => $actualRefund]);

            return response()->json([
                'disco_name' => strtoupper($bill_plan->disco_name),
                'request-id' => $transid,
                'amount' => $request->amount,
                'charges' => $charges,
                'status' => 'fail',
                'message' => 'Transaction fail ' . strtoupper($bill_plan->disco_name) . ' ' . strtoupper($request->meter_type) . ' ₦' . $request->amount . ' to ' . $request->meter_number,
                'meter_number' => $request->meter_number,
                'meter_type' => strtoupper($request->meter_type),
                'oldbal' => $user->bal,
                'newbal' => $actualRefund,
                'system' => $system,
                'wallet_vending' => 'wallet',
            ]);
        }

        // Processing / Other
        return response()->json([
            'disco_name' => strtoupper($bill_plan->disco_name),
            'request-id' => $transid,
            'amount' => $request->amount,
            'charges' => $charges,
            'status' => 'process',
            'message' => 'Transaction on process ' . strtoupper($bill_plan->disco_name) . ' ' . strtoupper($request->meter_type) . ' ₦' . $request->amount . ' to ' . $request->meter_number,
            'meter_number' => $request->meter_number,
            'meter_type' => strtoupper($request->meter_type),
            'oldbal' => $user->bal,
            'newbal' => $debit,
            'system' => $system,
            'wallet_vending' => 'wallet',
        ]);
    }
}
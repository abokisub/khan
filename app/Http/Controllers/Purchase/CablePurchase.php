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


class CablePurchase extends Controller
{

    public function BuyCable(Request $request)
    {
        $auth = $this->resolveCableAuthContext($request);
        if ($auth instanceof \Illuminate\Http\JsonResponse) {
            return $auth;
        }

        $user = $this->resolveUser($auth['accessToken']);
        if (!$user) {
            return response()->json(['status' => 'fail', 'message' => 'Invalid Access Token'])->setStatusCode(403);
        }

        $validator = $this->getCableValidator($request, $auth['type']);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'status' => 'fail',
                'reference' => 'CABLE_' . substr(md5(uniqid(mt_rand(), true)), 0, 13)
            ])->setStatusCode(403);
        }

        $reference = 'CABLE_' . substr(md5(uniqid(mt_rand(), true)), 0, 13);
        return $this->processCablePayment($request, $user, $auth['transid'], $auth['system'], $reference);
    }

    private function resolveCableAuthContext(Request $request)
    {
        $explode_url = explode(',', config('app.habukhan_app_key'));

        if (config('app.habukhan_device_key') == $request->header('Authorization')) {
            $verified_id = $this->verifyapptoken($request->user_id);
            $user = DB::table('user')->where(['id' => $verified_id, 'status' => 1])->first();

            if (!$user) {
                return ['accessToken' => 'null', 'transid' => $this->purchase_ref('CABLE_'), 'system' => 'APP', 'type' => 'APP'];
            }

            if ($this->core()->allow_pin == 1 && trim($user->pin) != trim($request->pin)) {
                Log::warning("Cable PIN Validation Failed for user: {$user->username}. Sent: [" . ($request->pin ?? 'NULL') . "], Stored: [" . ($user->pin ?? 'NULL') . "]");
                return response()->json(['status' => 'fail', 'message' => 'Invalid Transaction Pin'])->setStatusCode(403);
            }

            return [
                'accessToken' => $user->apikey,
                'transid' => $request->input('request-id') ?? $this->purchase_ref('CABLE_'),
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
                'transid' => $this->purchase_ref('CABLE_'),
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

    private function getCableValidator(Request $request, $type)
    {
        $rules = [
            'cable' => 'required',
            'iuc' => 'required',
            'bypass' => 'required',
            'cable_plan' => 'required',
        ];

        if ($type === 'APP') {
            $rules['user_id'] = 'required';
        } elseif ($type === 'WEB') {
            $rules['bypass'] = 'boolean|required';
        } elseif ($type === 'API') {
            $rules['request-id'] = 'required|unique:cable,transid';
        }

        return Validator::make($request->all(), $rules);
    }

    private function processCablePayment(Request $request, $user, $transid, $system, $reference)
    {
        if (DB::table('block')->where(['number' => $request->iuc])->count() > 0) {
            return response()->json(['status' => 'fail', 'message' => 'Number Block'])->setStatusCode(403);
        }

        $cable = DB::table('cable_id')->where('plan_id', $request->cable)->first();
        if (!$cable) {
            return response()->json(['status' => 'fail', 'message' => 'Invalid Cable Plan ID'])->setStatusCode(403);
        }

        if (DB::table('cable')->where('transid', $transid)->count() > 0 || DB::table('message')->where('transid', $transid)->count() > 0) {
            return response()->json(['status' => 'fail', 'message' => 'Referrence ID Used'])->setStatusCode(403);
        }

        $cable_name = strtolower($cable->cable_name);
        $cable_plan = DB::table('cable_plan')->where(['plan_id' => $request->cable_plan, 'cable_name' => $cable->cable_name, 'plan_status' => 1])->first();
        if (!$cable_plan) {
            return response()->json(['status' => 'fail', 'message' => 'Invalid Cable Plan ID'])->setStatusCode(403);
        }

        $cable_lock = DB::table('cable_result_lock')->first();
        if ($cable_lock->$cable_name != 1) {
            return response()->json(['status' => 'fail', 'message' => strtoupper($cable_name) . " is not available right now"])->setStatusCode(403);
        }

        if (!is_numeric($user->bal) || $user->bal <= 0) {
            return response()->json(['status' => 'fail', 'message' => 'Insufficient Account Kindly Fund Your Wallet'])->setStatusCode(403);
        }

        $cable_setting = DB::table('cable_charge')->first();
        $charges = ($cable_setting->direct == 1) ? $cable_setting->$cable_name : ($cable_plan->plan_price / 100) * $cable_setting->$cable_name;
        $total_amount = $charges + $cable_plan->plan_price;

        DB::beginTransaction();
        $user = DB::table('user')->where(['id' => $user->id])->lockForUpdate()->first();

        if ($user->bal < $total_amount) {
            return response()->json(['status' => 'fail', 'message' => 'Insufficient Account Kindly Fund Your Wallet'])->setStatusCode(403);
        }

        // Validate IUC
        $cable_sel = DB::table('cable_sel')->first();
        $adm = new IUCsend();
        $check_now = $cable_sel->$cable_name;
        $sending_data = ['iuc' => $request->iuc, 'cable' => $request->cable];

        if (method_exists($adm, $check_now)) {
            $customer_name = $adm->$check_now($sending_data);
        } else {
            Log::error("CablePurchase IUC Error: Method {$check_now} does not exist in IUCsend.");
            $customer_name = null;
        }

        if (empty($customer_name) && ($request->bypass == false || $request->bypass == 'false')) {
            $errorMessage = (strpos($cable_name, 'showmax') !== false) ? 'Invalid Phone Number or Service Unavailable' : 'Invalid IUC Number or Service Unavailable';
            return response()->json(['status' => 'fail', 'message' => $errorMessage])->setStatusCode(403);
        }

        // Debit
        $debit = $user->bal - $total_amount;
        if (!DB::table('user')->where(['id' => $user->id])->update(['bal' => $debit])) {
            return response()->json(['status' => 'fail', 'message' => 'unable to debit user'])->setStatusCode(403);
        }

        DB::commit();

        return $this->finalizeCableTransaction($request, $user, $cable, $cable_plan, $transid, $charges, $total_amount, $debit, $customer_name, $system, $reference, $check_now);
    }

    private function finalizeCableTransaction($request, $user, $cable, $cable_plan, $transid, $charges, $total_amount, $debit, $customer_name, $system, $reference, $check_now)
    {
        $cable_name = strtolower($cable->cable_name);
        $trans_history = [
            'username' => $user->username,
            'amount' => $total_amount,
            'message' => "⏳ Processing " . strtoupper($cable_name) . " " . $cable_plan->plan_name . " ₦" . $cable_plan->plan_price . " to " . $request->iuc . "...",
            'phone_account' => $request->iuc,
            'oldbal' => $user->bal,
            'newbal' => $debit,
            'habukhan_date' => $this->system_date(),
            'plan_status' => 0,
            'transid' => $transid,
            'role' => 'cable',
            'service_type' => 'TV',
            'transaction_channel' => 'EXTERNAL'
        ];

        $cable_trans = [
            'username' => $user->username,
            'amount' => $cable_plan->plan_price,
            'charges' => $charges,
            'cable_name' => strtoupper($cable_name),
            'cable_plan' => $cable_plan->plan_name,
            'plan_status' => 0,
            'iuc' => $request->iuc,
            'plan_date' => $this->system_date(),
            'transid' => $transid,
            'customer_name' => $customer_name,
            'system' => $system,
            'oldbal' => $user->bal,
            'newbal' => $debit,
        ];

        if (!$this->inserting_data('message', $trans_history) || !$this->inserting_data('cable', $cable_trans)) {
            DB::table('user')->where(['username' => $user->username, 'id' => $user->id])->update(['bal' => $user->bal]); // Refund attempt
            return response()->json(['status' => 'fail', 'message' => 'Unable to insert'])->setStatusCode(403);
        }

        $sender = new CableSend();
        $user_info = ['username' => $user->username, 'transid' => $transid, 'plan_id' => $request->cable_plan];

        if (method_exists($sender, $check_now)) {
            $response = $sender->$check_now($user_info);
        } else {
            Log::error("CablePurchase Error: Method {$check_now} does not exist in CableSend.");
            $response = 'fail';
        }

        return $this->handleCableProviderResponse($request, $user, $cable, $cable_plan, $transid, $charges, $debit, $response, $system, $reference);
    }

    private function handleCableProviderResponse($request, $user, $cable, $cable_plan, $transid, $charges, $debit, $response, $system, $reference)
    {
        $cable_name = strtolower($cable->cable_name);
        $receiptService = new ReceiptService();

        if ($response == 'success') {
            try {
                Beneficiary::updateOrCreate(
                    ['user_id' => $user->id, 'service_type' => 'tv', 'identifier' => $request->iuc],
                    ['network_or_provider' => $cable->cable_name, 'last_used_at' => Carbon::now()]
                );
            } catch (\Exception $e) {
                Log::error('Cable Beneficiary Save Failed: ' . $e->getMessage());
            }

            $successMessage = $receiptService->getFullMessage('TV', [
                'package' => $cable_plan->plan_name,
                'recipient' => $request->iuc,
                'amount' => $cable_plan->plan_price,
                'reference' => $transid,
                'status' => 'SUCCESS',
                'provider' => $cable->cable_name
            ]);

            DB::table('message')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 1, 'message' => $successMessage]);
            DB::table('cable')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 1]);

            try {
                (new \App\Services\NotificationService())->sendCableNotification($user, $cable_plan->plan_price, $cable_name, $cable_plan->plan_name, $request->iuc, $transid);
            } catch (\Exception $e) {
                Log::error("Cable Notification Error: " . $e->getMessage());
            }

            return response()->json([
                'cable_name' => strtoupper($cable_name),
                'request-id' => $transid,
                'amount' => $cable_plan->plan_price,
                'charges' => $charges,
                'status' => 'success',
                'transid' => $transid,
                'message' => 'successfully purchase ' . strtoupper($cable_name) . ' ' . $cable_plan->plan_name . ' ₦' . $cable_plan->plan_price . ' to ' . $request->iuc,
                'iuc' => $request->iuc,
                'oldbal' => $user->bal,
                'newbal' => $debit,
                'system' => $system,
                'wallet_vending' => 'wallet',
                'plan_name' => $cable_plan->plan_name,
                'reference' => $reference,
            ]);
        }

        if ($response == 'fail') {
            $refund = $user->bal; // user->bal was initial bal
            DB::table('user')->where(['username' => $user->username, 'id' => $user->id])->update(['bal' => $refund]);

            $failMessage = "❌ TV Subscription Failed\n\nYou attempted to subscribe " . strtoupper($cable_name) . " " . $cable_plan->plan_name . " for " . $request->iuc . " but the transaction failed. Your wallet has been refunded.";
            DB::table('message')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 2, 'newbal' => $refund, 'message' => $failMessage]);
            DB::table('cable')->where(['username' => $user->username, 'transid' => $transid])->update(['plan_status' => 2, 'newbal' => $refund]);

            return response()->json([
                'cabl_name' => strtoupper($cable_name),
                'request-id' => $transid,
                'amount' => $cable_plan->plan_price,
                'charges' => $charges,
                'status' => 'fail',
                'message' => 'Transaction fail ' . strtoupper($cable_name) . ' ' . $cable_plan->plan_name . ' ₦' . $cable_plan->plan_price . ' to ' . $request->iuc,
                'iuc' => $request->iuc,
                'oldbal' => $user->bal,
                'newbal' => $refund,
                'system' => $system,
                'wallet_vending' => 'wallet',
                'plan_name' => $cable_plan->plan_name,
                'reference' => $reference,
            ]);
        }

        // Processing / Other
        return response()->json([
            'cabl_name' => strtoupper($cable_name),
            'request-id' => $transid,
            'amount' => $cable_plan->plan_price,
            'charges' => $charges,
            'status' => 'process',
            'message' => 'Transaction on process ' . strtoupper($cable_name) . ' ' . $cable_plan->plan_name . ' ₦' . $cable_plan->plan_price . ' to ' . $request->iuc,
            'iuc' => $request->iuc,
            'oldbal' => $user->bal,
            'newbal' => $debit,
            'system' => $system,
            'wallet_vending' => 'wallet',
            'plan_name' => $cable_plan->plan_name,
            'reference' => $reference,
        ]);
    }
}
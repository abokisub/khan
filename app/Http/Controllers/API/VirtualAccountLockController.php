<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VirtualAccountLockController extends Controller
{
    /**
     * Get all virtual account lock statuses
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLocks(Request $request)
    {
        try {
            $locks = DB::table('virtual_account_locks')
                ->orderBy('provider')
                ->orderBy('account_type')
                ->get();

            return response()->json([
                'status' => 'success',
                'locks' => $locks,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch lock statuses',
            ], 500);
        }
    }

    /**
     * Toggle lock status for a specific provider-account combination
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleLock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|string|in:xixapay,monnify,paystack,paymentpoint',
            'account_type' => 'required|string|in:palmpay,kolomonie,moniepoint,wema',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid provider or account type',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $lock = DB::table('virtual_account_locks')
                ->where('provider', $request->provider)
                ->where('account_type', $request->account_type)
                ->first();

            if (!$lock) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Lock configuration not found',
                ], 404);
            }

            // Toggle the lock status
            DB::table('virtual_account_locks')
                ->where('provider', $request->provider)
                ->where('account_type', $request->account_type)
                ->update([
                    'is_locked' => !$lock->is_locked,
                    'updated_at' => now(),
                ]);

            $newStatus = !$lock->is_locked ? 'locked' : 'unlocked';

            return response()->json([
                'status' => 'success',
                'message' => ucfirst($request->provider) . ' (' . ucfirst($request->account_type) . ') has been ' . $newStatus,
                'is_locked' => !$lock->is_locked,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle lock status',
            ], 500);
        }
    }

    /**
     * Update lock status (set to specific value)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateLock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|string|in:xixapay,monnify,paystack,paymentpoint',
            'account_type' => 'required|string|in:palmpay,kolomonie,moniepoint,wema',
            'is_locked' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            DB::table('virtual_account_locks')
                ->where('provider', $request->provider)
                ->where('account_type', $request->account_type)
                ->update([
                    'is_locked' => $request->is_locked,
                    'updated_at' => now(),
                ]);

            $status = $request->is_locked ? 'locked' : 'unlocked';

            return response()->json([
                'status' => 'success',
                'message' => ucfirst($request->provider) . ' (' . ucfirst($request->account_type) . ') has been ' . $status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update lock status',
            ], 500);
        }
    }
}

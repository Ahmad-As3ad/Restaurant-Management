<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Http\Requests\Wallet\DepositRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    // عرض رصيد المحفظة
    public function balance(Request $request)
    {
        $wallet = $request->user()->wallet;

        if (!$wallet) {
            $wallet = $this->createWallet($request->user()->id);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $wallet->balance,
                'total_deposited' => $wallet->total_deposited,
                'total_spent' => $wallet->total_spent,
            ]
        ]);
    }

    // شحن المحفظة
    public function deposit(DepositRequest $request)
    {
        $user = $request->user();
        $wallet = $user->wallet;

        if (!$wallet) {
            $wallet = $this->createWallet($user->id);
        }

        DB::beginTransaction();

        try {
            $amount = $request->amount;
            $description = $request->description ?? 'Deposit to wallet';

            $transaction = $wallet->deposit($amount, $description);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Wallet deposited successfully',
                'data' => [
                    'new_balance' => $wallet->balance,
                    'transaction' => $transaction,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Deposit failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // سجل المعاملات
    public function transactions(Request $request)
    {
        $user = $request->user();

        $transactions = WalletTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    // سجل المعاملات مع فلتر حسب النوع
    public function transactionsByType(Request $request, string $type)
    {
        $user = $request->user();

        $validTypes = ['deposit', 'payment', 'refund', 'cancellation_fee'];

        if (!in_array($type, $validTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid transaction type',
                'valid_types' => $validTypes,
            ], 400);
        }

        $transactions = WalletTransaction::where('user_id', $user->id)
            ->where('type', $type)
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    // إنشاء محفظة للمستخدم (دالة مساعدة)
    private function createWallet(int $userId): Wallet
    {
        return Wallet::create([
            'user_id' => $userId,
            'balance' => 0,
            'total_deposited' => 0,
            'total_spent' => 0,
        ]);
    }
}

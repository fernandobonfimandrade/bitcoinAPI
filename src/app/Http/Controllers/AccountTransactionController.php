<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;   

class AccountTransactionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function getBalanceTransactionType($user_id,$transaction_type){
        try {

            $sum = AccountTransaction::select('amount')
                    ->where('user_id', $user_id)
                    ->where('transaction_type', $transaction_type )
                    ->sum('amount');


            return $sum;
        } catch (\Exception $e) {
            //return error message
            return 0;
        }
    }

    public function balance()
    {
        try {
            $user = Auth::user();
            $transactions = AccountTransaction::where('user_id', $user->id)->get();
            $balance = 0.00;
            foreach ($transactions as $key => $transaction) {
                $balance += $transaction->amount * $transaction->transaction_type;
            }

            return response()->json(['balance' => $balance, 'message' => 'OK'], 200);
        } catch (\Exception $e) {
            //return error message
            return response()->json(['message' => 'Failed to get balance!', 'error' => $e], 409);
        }
    }
    public function statement()
    {
        try {
            $user = Auth::user();
            $transactions = AccountTransaction::where('user_id', $user->id)->get();

            return response()->json(['account_transaction' => $transactions, 'message' => 'OK'], 200);
        } catch (\Exception $e) {
            //return error message
            return response()->json(['message' => 'Failed to get statement!', 'error' => $e], 409);
        }
    }

    public function deposit(Request $request)
    {

        //validate incoming request 
        $this->validate($request, [
            'amount' => 'required|numeric|min:1|max:999999999.99'
        ]);
        try {
            $user = Auth::user();

            $transaction = new AccountTransaction;
            $transaction->amount = $request->input('amount');
            $transaction->user_id = $user->id;
            $transaction->transaction_type = 1;
            $transaction->description = 'Deposit';
            $date = new \DateTime('NOW');
            $transaction->transaction_date = $date->format('Y-m-d H:i:s');

            $credit = $this->getBalanceTransactionType($user->id,1);
            $debit = $this->getBalanceTransactionType($user->id,-1);

            $balance = $credit - $debit;
            
            $transaction->before_balance = $balance;
            $transaction->after_balance = $balance+$transaction->amount;

            $transaction->save();

            return response()->json(['account_transactions' => $transaction, 'message' => 'CREATED'], 201);
        } catch (\Exception $e) {
            //return error message
            return response()->json(['message' => 'Deposit Failed!', 'error' => $e], 409);
        }
        return $request;
    }
    
}

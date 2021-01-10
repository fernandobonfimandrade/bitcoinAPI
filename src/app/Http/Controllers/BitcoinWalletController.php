<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;   
use Illuminate\Support\Facades\Auth;
use App\Models\AccountTransaction;
use App\Models\BitcoinTransaction;
use App\Models\BitcoinSelling;

/**
 * @method float getBitcoinPrice()
 * @method float getBitcoinBalance()
 * @method json price()
 * @method json buy(Request $request)
 * @method json sell(Request $request)
 */
class BitcoinWalletController extends Controller
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

    private function getBitcoinPrice(){
        $data = Http::get('https://www.mercadobitcoin.net/api/BTC/ticker/');

            if($data->status() == 200){
                $body = json_decode($data->body());
                return $body->ticker->buy;
            }else{
                return NULL;
            }
    }

    public function getBitcoinBalance()
    {
        try {
            $user = Auth::user();
            $transactions = BitcoinTransaction::where('user_id', $user->id)->get();
            $balance = 0.00;
            foreach ($transactions as $key => $transaction) {
                $balance += $transaction->bt_amount * $transaction->transaction_type;
            }

            return $balance;
        } catch (\Exception $e) {
            //return error message
            return NULL;
        }
    }


    public function price()
    {
        try {

            return response()->json(['bitcoin_price' => $this->getBitcoinPrice() , 'message' => 'OK'], 200);
           

        } catch (\Exception $e) {
            //return error message
            return response()->json(['message' => 'Failed to get bitcoin price!', 'error' => $e], 409);
        }
    }

    public function buy(Request $request)
    {
        //validate incoming request 
        $this->validate($request, [
            'amount' => 'required|numeric|min:1|max:999999999.99'
        ]);

        try {
            $user = Auth::user();

            $BitcoinPrice = $this->getBitcoinPrice();
            $investiment = $request->input('amount');

            $transactions = AccountTransaction::where('user_id', $user->id)->get();
            $balance = 0.00;
            foreach ($transactions as $key => $transaction) {
                $balance += $transaction->amount * $transaction->transaction_type;
            }
            if($balance >= $investiment && $BitcoinPrice > 0 ){

                $bt_amount = $investiment/$BitcoinPrice;

                $bitcoinTransaction = new BitcoinTransaction;
                $bitcoinTransaction->bt_amount = $bt_amount;
                $bitcoinTransaction->cash_amount = $investiment;
                $bitcoinTransaction->bt_price = $BitcoinPrice;
                $bitcoinTransaction->user_id = $user->id;
                $bitcoinTransaction->transaction_type = 1;
                $bitcoinTransaction->description = 'buy';
                $date = new \DateTime('NOW');
                $bitcoinTransaction->transaction_date = $date->format('Y-m-d H:i:s');

                $bitcoinTransaction->save();



                $accountTransaction = new AccountTransaction;
                $accountTransaction->amount = $investiment;
                $accountTransaction->user_id = $user->id;
                $accountTransaction->transaction_type = -1;
                $accountTransaction->description = 'Debit';
                $accountTransaction->transaction_date = $date->format('Y-m-d H:i:s');
                
                $accountTransaction->before_balance = $balance;
                $accountTransaction->after_balance = $balance-$investiment;
    
                $accountTransaction->save();


                return response()->json(['bitCoin_transaction' => $bitcoinTransaction,
                                        'account_transaction' => $accountTransaction,
                                         'message' => 'OK'], 201);
            }else{
                return response()->json(['balance' => $balance ,'message' => 'insuficient funds!'], 200);
            }



        } catch (\Exception $e) {
            //return error message
            return response()->json(['message' => 'Failed to buy bitcoin!', 'error' => $e], 409);
        }
    }

    public function sell(Request $request)
    {
        //validate incoming request 
        $this->validate($request, [
            'amount' => 'required|numeric|min:000000.000001|max:999999.999999',
            'price' => 'required|numeric|min:1|max:999999999.99'
        ]);

        try {
            $user = Auth::user();

            $sellAmount = $request->input('amount');
            $sellPrice = $request->input('price');

            $bicoinBalance = $this->getBitcoinBalance();

            
            if($bicoinBalance >= $sellAmount ){

                $selling = new BitcoinSelling;
                $selling->user_id = $user->id;
                $selling->bt_amount = $sellAmount;
                $selling->bt_price = $sellPrice;
                $date = new \DateTime('NOW');
                $selling->intention_date = $date->format('Y-m-d H:i:s');

                $selling->save();

                return response()->json(['intentionToSell' => $selling, 'message' => 'OK'], 201);

            }else{
                return response()->json(['bitcoin_balance' => $bicoinBalance ,'message' => 'insuficient funds!'], 200);
            }
        } catch (\Exception $e) {
            //return error message
            return response()->json(['message' => 'Failed to buy bitcoin!', 'error' => $e], 409);
        }
    }

}

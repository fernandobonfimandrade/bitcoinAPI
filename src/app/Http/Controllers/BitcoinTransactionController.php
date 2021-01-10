<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;   
use Illuminate\Support\Facades\Auth;
use App\Models\AccountTransaction;
use App\Models\BitcoinTransaction;
use App\Models\BitcoinSelling;
use App\Services\Mailling;

/**
 * @method float getBitcoinPrice()
 * @method float getBitcoinBalance()
 * @method float getAmountSalesIntentions()
 * @method json price()
 * @method json buy(Request $request)
 * @method json sell(Request $request)
 * @method json processSalesItentions()
 * @method json pendingSalesItentions()
 * @method json statement()
 * @method json balance()
 * @method json volume()
 */
class BitcoinTransactionController extends Controller
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

    private function getBitcoinBalance()
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

    private function getAmountSalesIntentions()
    {
        try {
            $user = Auth::user();
            $intentions = BitcoinSelling::where('processed', false)->where('user_id', $user->id)->get();
            $amount = 0.00;
            foreach ($intentions as $key => $intention) {
                $amount += $intention->bt_amount;
            }

            return $amount;
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


                $mail = new Mailling();
                $subject = "BITCOIN INVESTMENT";
                $text = "Hi ".$user->name.".<br>";
                $text .= "Amount of biticoin purchased: ".number_format($bitcoinTransaction->bt_amount,9,'.','').".<br>";
                $text .= "Investment amount: R$ ".number_format($investiment,2,',','.').".<br>";
                $text .= "Investment date: ".$date->format('Y-m-d H:i:s').".<br>";
                $text .= "<hr>";
                $text .= "BITCOIN API";
                $mail->setBody($user->email, $user->name, $subject, $text);
                $bitcoinTransaction->mail = $mail->send();


                return response()->json(['bitCoin_transaction' => $bitcoinTransaction,
                                        'account_transaction' => $accountTransaction,
                                         'message' => 'CREATED'], 201);
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
            'amount' => 'required|numeric|min:0.000000001|max:999999.999999',
            'price' => 'required|numeric|min:1|max:999999999.99'
        ]);

        try {
            $user = Auth::user();

            $sellAmount = $request->input('amount');
            $sellPrice = $request->input('price');

            $bicoinBalance = $this->getBitcoinBalance();
            $amountSalesIntentions = $this->getAmountSalesIntentions();

            
            if($bicoinBalance-$amountSalesIntentions >= $sellAmount ){

                $selling = new BitcoinSelling;
                $selling->user_id = $user->id;
                $selling->bt_amount = $sellAmount;
                $selling->bt_price = $sellPrice;
                $date = new \DateTime('NOW');
                $selling->intention_date = $date->format('Y-m-d H:i:s');

                $selling->save();

                return response()->json(['intentionToSell' => $selling, 'message' => 'CREATED'], 201);

            }else{
                return response()->json(['bitcoin_balance' => number_format($bicoinBalance,9,'.',''), 
                                        'AmountSalesIntentions' => $amountSalesIntentions ,
                                        'AmountPretended' => $sellAmount ,
                                        'message' => 'insuficient funds!'], 200);
            }
        } catch (\Exception $e) {
            //return error message
            return response()->json(['message' => 'Failed to buy bitcoin!', 'error' => $e], 409);
        }
    }


    function processSalesItentions(){
        try {

            $intentions = BitcoinSelling::where('processed', false)->get();

            $BitcoinPrice = $this->getBitcoinPrice();

            $processed = array();

            foreach ($intentions as $key => $intention) {
               if((integer)$BitcoinPrice == (integer)$intention->bt_price){

                    $sellingAmount = $BitcoinPrice*$intention->bt_amount;

                    $bitcoinTransaction = new BitcoinTransaction;
                    $bitcoinTransaction->bt_amount = $intention->bt_amount;
                    $bitcoinTransaction->cash_amount = $sellingAmount;
                    $bitcoinTransaction->bt_price = $BitcoinPrice;
                    $bitcoinTransaction->user_id = $intention->user_id;
                    $bitcoinTransaction->transaction_type = -1;
                    $bitcoinTransaction->description = 'sell';
                    $date = new \DateTime('NOW');
                    $bitcoinTransaction->transaction_date = $date->format('Y-m-d H:i:s');
                    $bitcoinTransaction->save();

                    $intention->processed = true; 
                    $intention->selling_date = $date->format('Y-m-d H:i:s'); 
                    $intention->save();
                    $intention->usuario=$intention->usuario()->get();
                    

                    $transactions = AccountTransaction::where('user_id', $intention->user_id)->get();
                    $balance = 0.00;
                    foreach ($transactions as $key => $transaction) {
                        $balance += $transaction->amount * $transaction->transaction_type;
                    }
                    $accountTransaction = new AccountTransaction;
                    $accountTransaction->amount = $sellingAmount;
                    $accountTransaction->user_id = $intention->user_id;
                    $accountTransaction->transaction_type = 1;
                    $accountTransaction->description = 'Credit';
                    $accountTransaction->transaction_date = $date->format('Y-m-d H:i:s');
                    
                    $accountTransaction->before_balance = $balance;
                    $accountTransaction->after_balance = $balance+$sellingAmount;
        
                    $accountTransaction->save();


                    $mail = new Mailling();
                    $subject = "BITCOIN SALES INTENTION PROCESSED";
                    $text = "Hi ".$intention->usuario[0]->name.".<br>";
                    $text .= "Bitcoin amount: ".number_format($intention->bt_amount,9,'.','').".<br>";
                    $text .= "Cash amount: R$ ".number_format($sellingAmount,2,',','.').".<br>";
                    $text .= "Price expected: R$ ".number_format($intention->bt_price,2,',','.').".<br>";
                    $text .= "Intention date: ".$intention->intention_date.".<br>";
                    $text .= "<hr>";
                    $text .= "BITCOIN API";
                    $mail->setBody($intention->usuario[0]->email, $intention->usuario[0]->name, $subject, $text);
                    $intention->mail = $mail->send();



                    $processed[] = $intention;
               }
            }

            return response()->json(['SellingIntentionProcessed' => $processed, 'message' => 'OK'], 200);

        } catch (\Exception $e) {
            //return error message
            return response()->json(['message' => 'Failed to buy bitcoin!', 'error' => $e], 409);
        }
    }


    public function pendingSalesItentions()
    {
        try {
            $user = Auth::user();
            $intentions = BitcoinSelling::where('processed', false)->where('user_id', $user->id)->get();
            foreach ($intentions as $key => $intention) {
                $intention->usuario=$intention->usuario()->get();
            }

            return response()->json(['selling_intentions' => $intentions, 'message' => 'OK'], 200);
        } catch (\Exception $e) {
            //return error message
            return response()->json(['message' => 'Failed to get sales intentions!', 'error' => $e], 409);
        }
    }


    public function statement(Request $request)
    {
        //validate incoming request 
        $this->validate($request, [
            'from' => 'nullable|date',
            'to' => 'nullable|date'
        ]);

        try {
            
            if($request->input('from') && $request->input('to')){
                $from = $request->query('from');
                $to = $request->query('to');
            }else{
                $date = new \DateTime('NOW');
                $to = $date->format('Y-m-d H:i:s');
                $from = $date->sub(new \DateInterval('P90D'))->format('Y-m-d H:i:s');
            }

            $user = Auth::user();
            $transactions = BitcoinTransaction::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$from,$to])
            ->get();

            return response()->json(['bitcoin_transaction' => $transactions, 'message' => 'OK'], 200);
        } catch (\Exception $e) {
            //return error message
            return response()->json(['message' => 'Failed to get statement!', 'error' => $e], 409);
        }
    }


    public function balance()
    {

        try {

            $user = Auth::user();
            $transactions = BitcoinTransaction::where('user_id', $user->id)->get();
            $BitcoinPrice = $this->getBitcoinPrice();

            $balance = 0.00;
            foreach ($transactions as $key => $transaction) {
                $balance += $transaction->bt_amount * $transaction->transaction_type;
            }

            $cashAmount = $balance*$BitcoinPrice;

            return response()->json(['balance' => $balance,'cash_value' => $cashAmount, 'Actual_Bitcoin_Price' => $BitcoinPrice, 'message' => 'OK'], 200);
        } catch (\Exception $e) {
            //return error message
            return response()->json(['message' => 'Failed to get balance!', 'error' => $e], 409);
        }
    }

    public function volume()
    {

        try {

            $date = new \DateTime('NOW');
            
            $from = $date->format('Y-m-d').' 00:00:00';
            $to = $date->format('Y-m-d'.' 23:59:59');

            $transactions = BitcoinTransaction::whereBetween('transaction_date', [$from,$to])
            ->get();

            $sold = 0.00;
            $purchased = 0.00;
            foreach ($transactions as $key => $transaction) {
                $sold += $transaction->transaction_type == 1 ? $transaction->bt_amount : 0;
                $purchased += $transaction->transaction_type == -1 ? $transaction->bt_amount : 0;
            }


            return response()->json(['sold' => $sold,'purchased' => $purchased, 'message' => 'OK'], 200);
        } catch (\Exception $e) {
            //return error message
            return response()->json(['message' => 'Failed to get balance!', 'error' => $e], 409);
        }
    }

}

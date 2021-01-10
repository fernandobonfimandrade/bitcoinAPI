<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBitcoinTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bitcoin_transactions', function (Blueprint $table) {
            $table->id();            
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->on_delete('restrict');
            $table->string('description'); //deposit, credit, debit
            $table->integer('transaction_type'); //credit(+) 1 or debit(-) -1
            $table->double('bt_amount', 9, 9); // max per transaction 999.999,999999999
            $table->float('bt_price', 9, 2); // max per transaction 999.999.999,99
            $table->float('cash_amount', 9, 2); // max per transaction 999.999.999,99
            $table->dateTime('transaction_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bitcoin_transactions');
    }
}

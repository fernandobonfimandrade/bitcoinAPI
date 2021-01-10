<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->on_delete('restrict');
            $table->string('description'); //deposit, credit, debit
            $table->integer('transaction_type'); //credit(+) 1 or debit(-) -1
            $table->float('amount', 9, 2); // max per transaction 999.999.999,99
            $table->float('before_balance', 13, 2); // max 999.999.999.999,99
            $table->float('after_balance', 13, 2); // max 999.999.999.999,99
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
        Schema::dropIfExists('account_transactions');
    }
}

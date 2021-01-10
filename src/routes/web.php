<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});


// API route group
$router->group(['prefix' => 'api'], function () use ($router) {
    // Matches "/api/register
    $router->post('register', 'AuthController@register');
    
    // Matches "/api/login
    $router->post('login', 'AuthController@login');

    // API route group /account
    $router->group(['prefix' => 'account'], function () use ($router) {
        
        // Account balance "/api/account/balance
        $router->get('balance', 'AccountTransactionController@balance');
        
        // Account transactions Statement "/api/account/statement
        $router->get('statement', 'AccountTransactionController@statement');

        // Account transactions Deposit "/api/account/deposit
        $router->post('deposit', 'AccountTransactionController@deposit');
    
    });

    // API route group /bitcoin
    $router->group(['prefix' => 'bitcoin'], function () use ($router) {
    
        // bitcoin price "/api/bitcoin/price
        $router->get('price', 'BitcoinWalletController@price');

        // buy bitcoin "/api/bitcoin/buy
        $router->post('buy', 'BitcoinWalletController@buy');

        // sell bitcoin "/api/bitcoin/sell
        $router->post('sell', 'BitcoinWalletController@sell');
        
    
    });


 });
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BitcoinTransaction extends Model
{

    protected $fillable = [
        'user_id',
        'description',
        'transaction_type',
        'bt_amount',
        'bt_price',
        'cash_amount',
        'before_balance',
        'after_balance',
        'transaction_date'
    ];
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
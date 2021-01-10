<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountTransaction extends Model
{

    protected $fillable = [
        'user_id',
        'description',
        'transaction_type',
        'amount',
        'before_balance',
        'after_balance',
        'transaction_date'
    ];
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
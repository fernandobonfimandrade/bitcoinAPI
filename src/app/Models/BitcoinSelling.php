<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BitcoinSelling extends Model
{

    protected $fillable = [
        'user_id',
        'bt_amount',
        'bt_price',
        'application_date',
        'intention_date',
        'processed'
    ];
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
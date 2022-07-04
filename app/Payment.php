<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable =[
        'link',
        'user_id',
        'transaction_id',
        'type'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaction()
    {
        return  $this->belongsTo(Transaction::class);
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }
}

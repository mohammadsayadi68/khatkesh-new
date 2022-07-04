<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AuthCode extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'expire_at',
        'hash'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'user_id',
        'subject',
        'content',
    ];

    protected $casts = [
      'checked_at' => 'datetime',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }


}

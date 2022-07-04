<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VahdatRavieh extends Model
{
    protected $fillable = [
        'title',
        'date',
        'number',
        'text',
    ];

}

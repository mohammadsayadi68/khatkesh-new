<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppAction extends Model
{
    protected $fillable = [
        'name',
        'icon',
        'type',
        'action',
        'order',
        'premium',
        'disabled',
        'icon_type',
        'show_type',
        'discription'
    ];
    protected $casts =[
        'premium'=>'boolean',
        'disabled'=>'boolean',
    ];
}

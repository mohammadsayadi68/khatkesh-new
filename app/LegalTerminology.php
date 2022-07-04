<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LegalTerminology extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];
}

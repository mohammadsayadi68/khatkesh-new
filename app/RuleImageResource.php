<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RuleImageResource extends Model
{
    protected $fillable = [
        'name',
        'main_id',
        'rule_id'
    ];
}

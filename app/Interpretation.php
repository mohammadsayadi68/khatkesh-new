<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Interpretation extends Model
{
    protected $fillable = [
        'main_id'
    ];

    public function interpretationable()
    {
        return $this->morphTo();
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClauseBookmark extends Model
{
    protected $table = 'clause_bookmark';

    public function rule()
    {
        return $this->belongsTo(Rule::class);
    }

    public function clause()
    {
        return $this->belongsTo(Clause::class);
    }
}

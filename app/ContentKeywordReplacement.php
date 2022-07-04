<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContentKeywordReplacement extends Model
{
    public function keywordable()
    {
        return $this->morphTo();
    }
    public function originable()
    {
        return $this->morphTo();
    }
}

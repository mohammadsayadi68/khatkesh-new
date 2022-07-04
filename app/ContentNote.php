<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContentNote extends Model
{
    public function contentnoteable()
    {
        return $this->morphTo();
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContentReport extends Model
{
    public function contentreportable()
    {
        return $this->morphTo();
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TableImage extends Model
{
    public function tableimageable()
    {
        return $this->morphTo();
    }

}

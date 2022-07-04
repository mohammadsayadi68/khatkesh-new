<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $appends = [
        'has_used'
    ];
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function getHasUsedAttribute()
    {
        $id = \Auth::id();
        return $this->users()->find($id)!==null;
    }
}

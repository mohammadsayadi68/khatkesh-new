<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Chapter extends Model
{
    protected $fillable = [
        'main_id'
    ];
    public function chapterable()
    {
        return $this->morphTo();
    }
    public function seasons()
    {
        return $this->morphMany(Season::class,'seasonable');
    }
    public function episodes()
    {
        return $this->morphMany(Episode::class, 'episodeable');
    }
    public function clauses()
    {
        return $this->morphMany(Clause::class, 'clauseable');
    }
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = strip_tags($value);
    }

}

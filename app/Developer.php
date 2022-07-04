<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Developer extends Model
{
    protected $fillable = ['user_id', 'name', 'approved'];

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }
}

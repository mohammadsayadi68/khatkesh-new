<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CategoryExam extends Model
{
    protected $fillable = ['name', 'cover'];


    public function exams()
    {
        return $this->hasMany(Exam::class, 'category_id');
    }
}

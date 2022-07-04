<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GuardianCouncilAnswer extends Model
{
    protected $fillable  = [
        'rule_id',
        'guardian_council_category_id',
        'structure_id',
        'question_title',
        'question_description',
        'question_text',
        'question_footnote',
        'answer_description',
        'answer_text',
        'answer_footnote'
    ];
}

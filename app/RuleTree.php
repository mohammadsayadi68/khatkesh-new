<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RuleTree extends Model
{
    protected $with =['childs','ruletreeable'];
    protected $fillable = [
        'rule_id',
        'main_id'
    ];
    public function ruletreeable()
    {
        return $this->morphTo();
    }

    public function rule()
    {
        return $this->belongsTo(Rule::class);
    }

    public function childs()
    {
        return $this->hasMany(RuleTree::class,'parent_id','id');
    }

}

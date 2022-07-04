<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CategoryRule extends Model
{
    protected $fillable = [
        'title', 'description','resource_id'
    ];

    public function rules()
    {
        return $this->hasMany(Rule::class);
    }

}

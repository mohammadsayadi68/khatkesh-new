<?php

namespace App;

use App\Common\FullTextSearch;
use Illuminate\Database\Eloquent\Model;

class RuleItemContent extends Model
{
    use FullTextSearch;
    public $searchable = [
        'content'
    ];
    protected $fillable = ['main_id', 'rule_id'];
    protected $with = [
        'childs'
    ];
    static $types = [
        'عنوان' => 1,
        'مقدمه' => 2,
        'متن' => 3,
        'امضاء' => 4,
        'پیوست' => 5,
        'ماده' => 6,
        'بند' => 7,
        'جزء' => 8,
        'اصل' => 6,
        'فصل' => 9,
        'تفسیر' => 10,
        'مبحث' => 11,
        'موخره' => 12,
        'تبصره' => 13,
    ];

    public function childs()
    {
        return $this->hasMany(RuleItemContent::class, 'parent_id', 'id')
            ->orderBy('main_id')
            ->whereNotIn('name',['تفسیر']);
    }
}

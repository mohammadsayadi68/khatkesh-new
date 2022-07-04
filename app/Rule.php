<?php

namespace App;

//use App\Common\FullTextSearch;
//use Elasticquent\ElasticquentTrait;
use App\Common\FullTextSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Laravel\Scout\Searchable;

class Rule extends Model
{
    use SoftDeletes;
    use FullTextSearch;

    protected $mappingProperties = array(
        'content' => array(
            'type' => 'string',
            'analyzer' => 'standard'
        ),
        'title' => array(
            'type' => 'string',
            'analyzer' => 'standard'
        )
    );

    public $searchable = [
        'title',
//        'content'
    ];
    protected $fillable = [
        'main_id',
        'title',
        'type',
        'approval_date',
        'approval_authority',
        'implement_date',
        'type_name',
        'category_rule_id'
    ];
    protected $appends = [
        'type_name',
        'is_bookmarked',
        'is_special',
        'bookmark_icon_type'

    ];

    public function setTitleAttribute($value)
    {
        $value = str_replace('ي', 'ی', $value);
        $this->attributes['title'] = strip_tags($value);
    }

    public function setTextAttribute($value)
    {
        $this->attributes['text'] = str_replace('ي', 'ی', $value);
    }

    public function category()
    {
        return $this->belongsTo(CategoryRule::class,'category_rule_id','id');
    }


    public function getTypeNameAttribute()
    {
        $name = '';
        switch ($this->attributes['type']) {
            case \App\Constants\Rule::CONSTITUTION:
                $name = 'اساسی';
                break;
            case \App\Constants\Rule::NORMAL_LAW:
                $name = 'عادی';
                break;
            case \App\Constants\Rule::REGULATION:
                $name = 'آیین نامه';
                break;
        }
        return $name;
    }

    public function bookmarks()
    {
        return $this->belongsToMany(User::class, 'rule_bookmark');
    }

    public function specials()
    {
        return $this->belongsToMany(User::class, 'rule_special');
    }

    public function getWebIsBookmarkAttribute()
    {
        if (Auth::check()) {
            if ($this->bookmarks()->find(Auth::user()->id)) {
                return true;
            }
        }

        return false;
    }

    public function getBookmarkIconTypeAttribute()
    {
        if ($this->getWebIsBookmarkAttribute()) {
            return 'fa';
        } else {
            return 'far';
        }
    }

    public function getIsBookmarkedAttribute()
    {
        $user = \Auth::guard('api')->user();
        if (!$user)
            return false;
        if ($this->bookmarks()->find($user->id))
            return true;
        return false;
    }

    public function getIsSpecialAttribute()
    {
        $user = \Auth::guard('api')->user();
        if (!$user)
            return false;
        if ($this->specials()->find($user->id))
            return true;
        return false;
    }

    public function categoryMain()
    {
        return $this->belongsTo(Category::class);
    }


    public function getStatusApprovedAttribute()
    {

        switch ($this->attributes['status_approved']) {
            case 1:
                return 'معتبر';
                break;
            case 3:
                return 'آزمایشی';
                break;
            case 4:
                return 'موقت';
                break;
            case 6:
                return 'ساختار الحاقی';
                break;
            case 7:
                return 'منسوخه';
                break;
            case 9:
                return 'با اجرا منتفی می شود';
                break;
            case 14:
                return 'تنفیذ';
                break;
            case 15:
                return 'تمدید';
                break;
        }
    }

    public function getCountClausesAttribute()
    {
        return 0;
    }

    public function getNTypeAttribute()
    {
        switch ($this->attributes['n_type']) {
            case 1:
                return 'قانون عادی';
                break;

            case 2:
                return 'مقرره';
                break;

            case 3:
                return 'رأی';
                break;
        }
    }

    public function scopeNotHidden($query)
    {
        return $query->whereHidden(0);
    }

    public function resource()
    {
        return $this->hasOne(RuleResource::class, 'main_id', 'main_id');
    }

    public function images()
    {
        return $this->hasMany(RuleImageResource::class);
    }

    public function searchableAs()
    {
        return 'rules_index';
    }

    public function keywords()
    {
        return $this->hasMany(RuleKeyword::class);
    }
    public function items()
    {
        return $this->hasMany(RuleItemContent::class);
    }
    public function mainItems()
    {
        return $this->hasMany(RuleItemContent::class)->whereNull('parent_id');
    }

}

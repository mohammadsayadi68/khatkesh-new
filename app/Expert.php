<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Expert extends Model
{
    protected $guarded = ['id'];

    protected $appends = [
        'is_bookmarked',
    ];
    public function getIsBookmarkedAttribute()
    {
        $user = \Auth::guard('api')->user();
        if (!$user)
            return false;
        if ($this->bookmarks()->find($user->id))
            return true;
        return false;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookmarks()
    {
        return $this->belongsToMany(User::class, 'expert_bookmark');
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function field()
    {
        return $this->belongsTo(ExpertField::class);
    }

}

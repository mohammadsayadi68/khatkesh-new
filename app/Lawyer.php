<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Lawyer extends Model
{
    protected $guarded = ['id'];

    protected $appends = [
        'is_bookmarked',
        'expertises_name'
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
        return $this->belongsToMany(User::class, 'lawyer_bookmark');
    }

    public function expertises()
    {
        return $this->belongsToMany(Expertise::class);
    }

    public function getExpertisesNameAttribute()
    {
        return implode(',', $this->expertises->pluck('name')->toArray());
    }
    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function gradem()
    {
        return $this->belongsTo(LawyerGrade::class,'grade_id','id');
    }
}

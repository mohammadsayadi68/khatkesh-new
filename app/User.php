<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'phone'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'phone_verified_at' => 'datetime',
        'last_login_at' => 'datetime'
    ];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    public function scopeGenerateVerificationCode($query, $hash)
    {
        $code = rand(100000, 999999);
        AuthCode::create([
            'code' => $code,
            'user_id' => $this->id,
            'expire_at' => now()->addMinutes(10),
            'hash' => $hash
        ]);
        return $code;

    }

    public function bookmarks()
    {
        return $this->belongsToMany(Rule::class, 'rule_bookmark');
    }

    public function clauses()
    {
        return $this->belongsToMany(Clause::class, 'clause_bookmark');
    }

    public function experts()
    {
        return $this->belongsToMany(Expert::class, 'expert_bookmark');
    }

    public function lawyers()
    {
        return $this->belongsToMany(Lawyer::class, 'lawyer_bookmark');
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }


    public function specials()
    {
        return $this->belongsToMany(Rule::class, 'rule_special');
    }

    public function messageViews()
    {
        return $this->belongsToMany(NotificationMessage::class, 'notification_message_view');
    }

    public function vipIcon()
    {
        if ($this->attributes['vip'] == 1) {
            return "fa fa-check text-success";
        }
        return "fa fa-close text-danger";
    }

    public function examFeedback()
    {
        return $this->hasOne(ExamFeedback::class);
    }

    public function expert()
    {
        return $this->hasOne(Expert::class);
    }

    public function lawyer()
    {
        return $this->hasOne(Lawyer::class);
    }


}

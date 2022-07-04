<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotificationMessage extends Model
{
    protected $fillable =[
        'contact_id',
        'target',
        'title',
        'message'
    ];
    public function receivers()
    {
        return $this->belongsToMany(User::class,'notification_message_receiver');
    }
    public function views()
    {
        return $this->belongsToMany(User::class,'notification_message_view');
    }
}

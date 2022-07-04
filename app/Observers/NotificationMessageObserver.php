<?php

namespace App\Observers;

use App\NotificationMessage;
use App\User;

class NotificationMessageObserver
{
    /**
     * Handle the notification message "created" event.
     *
     * @param  \App\NotificationMessage  $notificationMessage
     * @return void
     */
    public function created(NotificationMessage $notificationMessage)
    {
        $target = $notificationMessage->target;
        if ($target=='ALL'){
            $users = User::whereNotNull('notification_key')->select('notification_key')->get()->pluck('notification_key')->toArray();
            $users = collect($users);
            $users = $users->chunk(1000);
            $chunks = $users->toArray();
            foreach ($chunks as $ids){
                $res = sendMessage([
                    'key'=>"1",
                    'title'=>$notificationMessage->title,
                    'message'=>$notificationMessage->message,
                    'id'=>$notificationMessage->id,
                ],$ids);
            }
        } else {
            $res = sendMessage([
                'key'=>"1",
                'title'=>$notificationMessage->title,
                'message'=>$notificationMessage->message,
                'id'=>$notificationMessage->id,
            ],$notificationMessage->receivers()->select('notification_key')->get()->pluck('notification_key')->toArray());
        }
    }

    /**
     * Handle the notification message "updated" event.
     *
     * @param  \App\NotificationMessage  $notificationMessage
     * @return void
     */
    public function updated(NotificationMessage $notificationMessage)
    {
        //
    }

    /**
     * Handle the notification message "deleted" event.
     *
     * @param  \App\NotificationMessage  $notificationMessage
     * @return void
     */
    public function deleted(NotificationMessage $notificationMessage)
    {
        //
    }

    /**
     * Handle the notification message "restored" event.
     *
     * @param  \App\NotificationMessage  $notificationMessage
     * @return void
     */
    public function restored(NotificationMessage $notificationMessage)
    {
        //
    }

    /**
     * Handle the notification message "force deleted" event.
     *
     * @param  \App\NotificationMessage  $notificationMessage
     * @return void
     */
    public function forceDeleted(NotificationMessage $notificationMessage)
    {
        //
    }
}

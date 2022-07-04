<?php

namespace App\Console\Commands;

use App\NotificationMessage;
use App\User;
use Illuminate\Console\Command;

class SendNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:send {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $notificationMessage = NotificationMessage::findOrFail($this->argument('id'));
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

        }
    }
}

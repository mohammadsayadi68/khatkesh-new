<?php

namespace App\Listeners;

use App\Common\SmsIR_SendMessage;
use App\Events\UserRegistered;

class SendSMSVerificationCode
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param UserRegistered $event
     * @return void
     */
    public function handle(UserRegistered $event)
    {
        $user = $event->user;
        $authCode = $user->generateVerificationCode($event->hash);
        $APIKey = "1860b5867d16d8eefa1e712";
        $SecretKey = "Amin@021";
        $APIURL = "https://ws.sms.ir/";
        // your code
        $Code = $authCode;
        $MobileNumber = $user->phone;
        if ($user->new_phone)
            $MobileNumber = $user->new_phone;
        $SmsIR_VerificationCode = new SmsIR_SendMessage($APIKey, $SecretKey, $APIURL);
        $VerificationCode = $SmsIR_VerificationCode->verificationCode($Code, $MobileNumber);
        \Log::info($VerificationCode);

    }
}

<?php

namespace App\Http\Controllers\Api;

use App\AuthCode;
use App\Events\UserRegistered;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    const KEY = '+4R%QSsx=rT%!E__';

    public function auth(Request $request)
    {
        $this->validate($request, [
            'phone' => 'required|iran_mobile',
        ]);
        $phone = $request->input('phone');
        $os = $request->input('os');
        $user = User::wherePhone($phone)->first();
        if (!$user)
            return $this->doRegister($phone,$os);
        return $this->doLogin($user,$os);

    }

    public function doRegister($phone,$os)
    {
        $user = User::create([
            'phone' => $phone,
        ]);
        if ($os && in_array($os,['android','ios'])){
            $user->os =$os;
            $user->save();
        }

        $hash = bcrypt(self::KEY);
        event(new UserRegistered($user, $hash));
        return response()->json([
            'type' => 'REGISTER',
            'nextAction' => 'VERIFICATION',
            'hash' => $hash
        ]);


    }

    public function doLogin(User $user,$os)
    {
        $hash = bcrypt(self::KEY);
        if ($os && in_array($os,['android','ios'])){
            $user->os =$os;
            $user->save();
        }
        event(new UserRegistered($user, $hash));
        return response()->json([
            'type' => 'LOGIN',
            'nextAction' => 'VERIFICATION',
            'hash' => $hash
        ]);
    }


    public function confirmVerification(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'hash' => 'required|string|hash_auth',
            'code' => 'required|numeric|digits:6',
            'notificationKey' => 'required|string'
        ]);
        $notificationKey = $request->input('notificationKey');
        if ($validator->fails())
            return response()->json([
                'message' => 'اطلاعات وارد شده معتبر نیستند',
                'status' => false
            ]);
        $hash = $request->input('hash');
        $code = $request->input('code');
        $authCode = AuthCode::whereCode($code)->whereHash($hash)->first();
        if (!$authCode)
            return response()->json([
                'message' => 'کد وارد شده صحیح نمیباشد',
                'status' => false
            ]);
        if ($authCode->type == 2) {
            return response()->json([
                'message' => 'کد وارد شده منقضی شده است',
                'status' => false
            ]);
        }
        if ($authCode->type == 1) {
            return response()->json([
                'message' => 'کد وارد شده قبلا استفاده شده است',
                'status' => false
            ]);
        }
        $authCode->type = 1;
        $authCode->save();
        $user = $authCode->user;

        $user->verified = 1;
        $user->phone_verified_at = now();
        $user->notification_key = $notificationKey;
        $user->save();


        $token = $this->generateApiToken($user);
        return [
            'message' => 'شما با موفقیت وارد شدید.',
            'status' => true,
            'apiToken' => $token,
            'role' => $user->role,
            'createdAtTimestamp' => $user->created_at->timestamp,
            'vip' => $user->vip,
            'avatar' => $user->avatar ? url($user->avatar) : null,
            'shouldUpdate'=>false
        ];

    }

    private function generateApiToken(User $user)
    {
        $token = Str::random(40);
        if ($user->api_token)
            $token = $user->api_token;
        $user->api_token = $token;
        $user->last_login_at = now();
        $user->save();
        return $token;
    }

    public function resendCode(Request $request)
    {
        $this->validate($request, [
            'phone' => 'required|iran_mobile',
            'hash' => 'required|string|hash_auth',
        ]);
        $phone = $request->input('phone');
        $user = User::wherePhone($phone)->firstOrFail();
        $hash = bcrypt(self::KEY);
        event(new UserRegistered($user, $hash));
        return response()->json([
            'nextAction' => 'VERIFICATION',
            'hash' => $hash
        ]);
    }


}

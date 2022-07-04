<?php

namespace App\Http\Controllers;

use App\AuthCode;
use App\Events\UserRegistered;
use App\User;
use Auth;
use Illuminate\Http\Request;

class AuthController extends Controller
{

    const KEY = '+4R%QSsx=rT%!E__';


    public function register()
    {
        return view('auth.register')->with(['title' => "ثبت نام"]);
    }


    public function login()
    {
        return view('auth.login')->with(['title' => "ورود"]);
    }


    public function auth(Request $request)
    {
        $this->validate($request, [
            'phone' => 'required|iran_mobile',
        ]);
        $phone = $request->input('phone');
        $user = User::wherePhone($phone)->first();
        if (!$user)
            return $this->doRegister($phone);
        return $this->doLogin($user);

    }

    public function doLogin(User $user)
    {
        $this->validate(request(), [
            'phone' => "required|numeric|regex:/^(09)+[0-9]{9}$/",
            'password' => "required|min:6|max:20",
        ]);

        $rememberme = request()->has('rememberme');
        if (Auth::attempt(['phone' => request()->input('phone'), 'password' => request()->input('password'), 'verified' => 1], $rememberme)) {
        		if(Auth::user()->role == 2) {
							return redirect()->route('admin.home')->with(['success' => "با موفقیت وارد شدید", 'title' => 'صفحه اصلی مدیریت']);
						}
            return redirect()->route('profile')->with(['success' => "با موفقیت وارد شدید", 'title' => 'صفحه اصلی']);
        }
        return redirect()->back()->with('error', 'شماره تلفن یا رمز عبور اشتباه است.');

    }


    public function doRegister(Request $request)
    {
        $this->validate($request, [
            'name' => "required|min:3|max:60",
            'phone' => "required|numeric|regex:/^(09)+[0-9]{9}$/|unique:users",
            'password' => "required|min:6|max:20|confirmed",
        ]);


        $newUser = new User();
        $newUser->name = $request->input('name');
        $newUser->phone = $request->input('phone');

        $newUser->password = $request->input('password');
        $newUser->save();
        $hash = bcrypt(self::KEY);
        event(new UserRegistered($newUser, $hash));

        \Session::put('verify-hash',$hash);
      return redirect(route('verify'));
    }

    public function verifyForm()
    {

        $hash = \Session::get('verify-hash');
        if (!$hash)
            return redirect('/login');
        return view('auth.verify')->with(['title' => "تایید شماره تلفن"]);

    }


    public function doVerify(Request $request)
    {
        $this->validate($request,[
            'code' => 'required|numeric|digits:6'
        ]);

        $hash = \Session::get('verify-hash');
        if (!$hash)
            return redirect('/login');
        $code = $request->input('code');
        $authCode = AuthCode::whereCode($code)->whereHash($hash)->first();
        if (!$authCode)
            return redirect()->back()->with('error', 'کد وارد شده صحیح نمیباشد');


        if ($authCode->type == 2) {
            return redirect()->back()->with('error', 'کد وارد شده منقضی شده است');

        }
        if ($authCode->type == 1) {
            return redirect()->back()->with('error', 'کد وارد شده قبلا استفاده شده است');
        }
        $authCode->type = 1;
        $authCode->save();
        $authCode->user->verified = 1;
        $authCode->user->phone_verified_at = now();
        $authCode->user->save();
        Auth::loginUsingId($authCode->user->id);

        return redirect()->route('profile')->with(['success' => "ثبت نام شما با موفقیت انجام شد و وارد پنل کاربری شدید.", 'title' => 'صفحه اصلی']);


    }


    public function resendCode()
    {
        $hash = \Session::get('verify-hash');
        if (!$hash)
            return redirect('/login');

        $authCode = AuthCode::whereHash($hash)->first();
        if (!$authCode)
            return redirect('/login');

        $hash = bcrypt(self::KEY);
        event(new UserRegistered($authCode->user, $hash));
        \Session::put('verify-hash',$hash);
        \Session::put('resent-code',true);
        return redirect()->route('profile')->with(['success' => "کد جدید با موفقیت ارسال شد.", 'title' => 'صفحه اصلی']);

    }


    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }
}

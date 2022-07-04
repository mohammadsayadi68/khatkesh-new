<?php

namespace App\Http\Controllers\Api;

use App\AppVersion;
use App\AuthCode;
use App\ClauseBookmark;
use App\Events\UserRegistered;
use App\Http\Controllers\Controller;
use App\Http\Resources\AppVersionResource;
use App\Http\Resources\BookmarkClausesCollection;
use App\Http\Resources\BookmarkRulesCollection;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function bookmarks()
    {
        $user = \Auth::user();
        $ids = $user->bookmarks->pluck('id')->toArray();
        return [
            'rules' => (new BookmarkRulesCollection($user->bookmarks))->resolve(),
            'ids' => $ids
        ];
    }

    public function clauseBookmarks()
    {
        $user = \Auth::user();
        $clauses = ClauseBookmark::whereUserId($user->id)->with('rule', 'clause')->get();
        return new BookmarkClausesCollection($clauses);
    }

    public function clauseBookmarkIds()
    {
        $user = \Auth::user();
        $clauseBookmarkIds = \Cache::remember('clause-bookmark-ids-' . $user->id, 30 * 24 * 60 * 60, function () use ($user) {
            return \DB::table('clause_bookmark')->where('user_id', $user->id)->get()->pluck('clause_id')->toArray();
        });
        return response()->json($clauseBookmarkIds);
    }

    public function bookmarkIds()
    {
        $user = \Auth::user();
        $ids = $user->bookmarks->pluck('id')->toArray();
        return response()->json($ids);
    }

    public function user()
    {
        $user = \Cache::remember('user-'.\Auth::id(),111111111,function (){
            return \Auth::user();
        });
        return response()->json([
            'name' => $user->name,
            'phone' => $user->phone,
            'avatar' => url($user->avatar),
        ]);
    }

    public function updateName(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string'
        ]);
        $user = \Auth::user();
        $user->name = $request->input('name');
        $user->save();
        return response()->json([
            'user' => [
                'name' => $user->name,
                'phone' => $user->phone,
            ]
        ]);
    }

    public function updatePhone(Request $request)
    {
        $this->validate($request, [
            'phone' => 'required|iran_mobile'
        ]);
        $phone = $request->input('phone');
        $user = User::wherePhone($phone)->first();
        if ($user)
            return response()->json([
                'status' => false,
                'message' => 'شماره تلفن وارد شده توسط شخص دیگری ثبت شده است.'
            ]);
        $user = \Auth::user();
        $user->new_phone = $phone;
        $user->save();
        $hash = bcrypt(AuthController::KEY);
        event(new UserRegistered($user, $hash));
        return response()->json([
            'status' => true,
            'message' => 'کد تاییدیه با موفقیت ارسال شد',
            'hash' => $hash
        ]);


    }

    public function confirmVerification(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'hash' => 'required|string|hash_auth',
            'code' => 'required|numeric|digits:6'
        ]);
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
        $user->phone = $user->new_phone;
        $user->new_phone = null;
        $user->save();


        return [
            'message' => 'شما با موفقیت وارد شدید.',
            'status' => true,
            'user' => [
                'name' => $user->name,
                'phone' => $user->phone,
            ]
        ];

    }

    public function resendCode(Request $request)
    {
        $this->validate($request, [
            'phone' => 'required|iran_mobile',
            'hash' => 'required|string|hash_auth',
        ]);
        $user = \Auth::user();
        $hash = bcrypt(AuthController::KEY);
        event(new UserRegistered($user, $hash));
        return response()->json([
            'nextAction' => 'VERIFICATION',
            'hash' => $hash
        ]);
    }

    public function uploadAvatar(Request $request)
    {
        $this->validate($request, [
            'avatar' => 'required|mimes:png,jpeg,jpg',
        ]);
        $avatar = $request->file('avatar');
        $filename = Str::random() . '.' . $avatar->getClientOriginalExtension();
        $avatar->move(public_path('/avatars'), $filename);
        $user = \Auth::user();
        $user->avatar = '/avatars/' . $filename;
        $user->save();
        \Cache::forget('experts');
        \Cache::forget('lawyers');
        return response()->json([
            'url' => url('/avatars/' . $filename),
        ]);
    }

    public function updateBaseInfo(Request $request)
    {
        $this->validate($request, [
            'version' => 'required|string',
            'versionCode' => 'required|numeric',
        ]);
        $version = $request->input('version');
        $versionCode = $request->input('versionCode');
        $user = \Auth::user();
        $oldVersionCode = $user->app_version_code;
        $shouldUpdateOfflineRules = false;
        if ($oldVersionCode != $versionCode) {
            $versionCodeUpdate = AppVersion::whereCode($versionCode)->first();
            if ($versionCodeUpdate)
                $shouldUpdateOfflineRules = $versionCodeUpdate->offline_rules_update;
        }
        $user->app_version = $version;
        $user->app_version_code = $versionCode;
        $user->last_used_at = now()->format('Y-m-d H:i:s');
        $user->save();
        $update = AppVersion::where('code', '>', $versionCode)->orderBy('code','DESC')->first();


        return response()->json([
            'message' => 'Successfully updated',
            'shouldUpdateOfflineRules'=>$shouldUpdateOfflineRules,
            'update'=> $update ? new AppVersionResource($update) : []
        ]);

    }


}

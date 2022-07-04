<?php

namespace App\Http\Controllers\Admin;

use App\Contact;
use App\Http\Controllers\Controller;
use App\NotificationMessage;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::latest()->paginate();
        $title = 'مدیریت کاربران';
        return view('admin.user.index', compact('title', 'users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $title = 'افزودن کاربر';
        return view('admin.user.create',compact('title'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request,[
           'name'=>'required|string',
           'phone'=>'required|numeric|unique:users',
           'password'=>'required|string',
           'points'=>'required|numeric'
        ]);
        $user = new User();
        $user->name = $request->input('name');
        $user->phone = $request->input('phone');
        $user->password = $request->input('password');
        $user->points = $request->input('points');
        $user->verified = 1;
        $user->save();
        message('کاربر جدید با موفقیت اضافه شد','success');
        return redirect(route('admin.user.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        $title = 'ویرایش کاربر';
        return view('admin.user.edit',compact('title','user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $this->validate($request,[
            'name'=>'required|string',
            'phone'=>'required|numeric|unique:users,phone,'.$user->id,
            'points'=>'required|numeric'
        ]);
        $user->name = $request->input('name');
        $user->phone = $request->input('phone');
        if ($request->input('password'))
            $user->password = $request->input('password');
        $user->points = $request->input('points');
        $user->save();
        message('اطلاعات کاربر با موفقیت بروزرسانی شد','info');
        return redirect(route('admin.user.index'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * @param Request $request
     * @throws \Illuminate\Validation\ValidationException
     */
    public function paradeDelete(Request $request)
    {
        $this->validate($request, [
            'users' => 'required|array',
            'users.*' => 'required|numeric',
        ]);
        User::whereIn('id', $request->input('users'))->delete();
        return response()->json(true);
    }

    /**
     * @param Request $request
     * @throws \Illuminate\Validation\ValidationException
     */
    public function paradeEnable(Request $request)
    {
        $this->validate($request, [
            'users' => 'required|array',
            'users.*' => 'required|numeric',
        ]);
        User::whereIn('id', $request->input('users'))->update(['verified' => 1]);
        return response()->json(true);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function paradeDisable(Request $request)
    {
        $this->validate($request, [
            'users' => 'required|array',
            'users.*' => 'required|numeric',
        ]);
        User::whereIn('id', $request->input('users'))->update(['verified' => 0]);
        return response()->json(true);
    }

    /**
     * @param Request $request
     * @throws \Illuminate\Validation\ValidationException
     */
    public function filter(Request $request)
    {
        $this->validate($request, [
            'data' => 'required|string'
        ]);
        parse_str($request->input('data'), $data);
        $validator = \Validator::make($data, [
            'sort-type' => 'required|string|in:DESC,ASC',
            'sort-column' => 'required|string|in:name,phone,id,verified,last_login_at,points',
            'page' => 'required|numeric'
        ]);
        if ($validator->fails())
            throw new ValidationException($validator);
        $sortColumn = $data['sort-column'];
        $sortType = $data['sort-type'];
        $page = $data['page'];
        $name = $data['name'];
        $phone = $data['phone'];
        $status = $data['status'];
        $vip = $data['vip'];
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $users = User::orderBy($sortColumn, $sortType);
        if ($name)
            $users = $users->where('name', 'LIKE', '%' . $name . '%');
        if ($phone)
            $users = $users->where('phone', 'LIKE', '%' . $phone . '%');
        if ($status != '')
            $users = $users->whereVerified($status);
        if ($vip != '' && $vip != 'all')
            $users = $users->whereVip($vip);
        $users = $users->paginate(10);
        return response()->view('admin.user.users-partial', compact('users'));

    }


	public function message()
	{
		$title = "ارسال پیام به کاربران";
		return view('admin.message.create')->with(['title' => $title]);
	}



	public function searchUsers(Request $request)
	{
		$keyword = $request->input('keyword');
		$users = User::where('name', 'LIKE' , "%" . $keyword . "%")->orWhere('phone', "LIKE" , "%" . $keyword . "%")->get();
		$html = view('admin.user.selectable-users-list', compact('users'));
		return $html;
	}

	public function selectedUserItem(Request $request)
	{
		$user = User::findOrFail($request->input('id'));
		$html = view('admin.user.selected-user-item', compact('user'));
		return $html;
	}


	public function sendMessage(Request $request)
	{
		$this->validate($request, [
			'title' => 'required|string|min:2',
			'message' => 'required|string|min:4',
			'target' => 'required|string|in:ALL,CUSTOM',
			'selected-users.*' => 'numeric|exists:users,id',
			'selected-users' => 'array',
		]);

		$target = $request->input('target');
		$selectedUsers = $request->input('selected-users');
		$request = $request->except('selected-users');
		if($target == 'ALL') {
			NotificationMessage::create($request);
		} else if($target == 'CUSTOM') {
			$notificationMessage = NotificationMessage::create($request);
			$notificationMessage->receivers()->sync($selectedUsers);
            $users = User::whereIn('id',$selectedUsers)->whereNotNull('notification_key')->select('notification_key')->get()->pluck('notification_key')->toArray();
            sendMessage([
                'key'=>"1",
                'title'=>$notificationMessage->title,
                'message'=>$notificationMessage->message,
                'id'=>$notificationMessage->id,
            ],$users);
		}
		message('درخواست ارسال پیام، با موفقیت ثبت شد', 'success');
		return redirect()->back();




	}
}


<?php

namespace App\Http\Controllers;

use App\ClauseBookmark;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
	public function profile()
	{
		return view('profile.profile');
	}

	public function changePassword()
	{
		return view('profile.change-password');
	}

	public function doChangePassword()
	{
		$this->validate(request(), [
			'current_password' => "required|min:6|max:20",
			'password' => "required|min:6|max:20|confirmed",
		]);

		$current_password = request()->input('current_password');
		$password = request()->input('password');
		$user = User::find(Auth::user()->id);
		if(!Hash::check($current_password, $user->password)) {
			return back()->with(['error' => "رمز عبور فعلی اشتباه است."]);
		}
		$user->password = $password;
		$user->save();
		return view('profile.profile')->with(['success' => "رمز با موفقیت تغییر کرد."]);
	}

	public function bookmarks() {
		$clauses = ClauseBookmark::where('user_id',Auth::user()->id)->with('rule','clause')->get();
		$rules = Auth::user()->bookmarks()->get();
		return view('profile.bookmarks', compact('rules', 'clauses'));
	}
}

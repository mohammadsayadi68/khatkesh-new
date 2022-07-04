<?php

namespace App\Http\Controllers;

use App\Clause;
use App\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
	public function switchBookmark(Request $request)
	{
		$type = $request->input('type');
		$rule_id = $request->input('rule_id');
		if ($type == 'clause') {
			$clause_id = $request->input('clause_id');
			$clause = Clause::findOrFail($clause_id);
			if ($clause->getWebIsBookmarkAttribute()) {
				$clause->bookmarks()->detach(Auth::user()->id);
			} else {
				$clause->bookmarks()->attach([Auth::user()->id => [
					'rule_id' => $rule_id
				]]);
			}
			return ['success' => true];
		}

		$rule = Rule::findOrFail($rule_id);
		if ($rule->getWebIsBookmarkAttribute()) {
			$rule->bookmarks()->detach(Auth::user()->id);
		} else {
			$rule->bookmarks()->attach([Auth::user()->id => [
				'rule_id' => $rule_id
			]]);
		}
		return ['success' => true];
	}
}

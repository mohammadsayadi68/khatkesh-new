<?php

namespace App\Http\Controllers\Api;

use App\Category;
use App\CategoryRule;
use App\Contact;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryCollection;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    public function categories()
    {
        $categories = \Cache::remember('approval-authorities', 30 * 24 * 60 * 60, function () {
            return CategoryRule::select('title', 'id')->whereEnabled(1)->get();
        });
        return new CategoryCollection($categories);
    }

    public function baseCategories()
    {
        $categories = \Cache::remember('base-categories', 30 * 24 * 60 * 60, function () {
            return Category::select('title', 'id')->whereEnabled(1)->get();
        });
        return new CategoryCollection($categories);
    }

    public function contactSubmit(Request $request)
    {
        $this->validate($request, [
            'subject' => 'required|string',
            'content' => 'required|string'
        ]);
        Contact::create(array_merge($request->all(['subject', 'content']),[
            'user_id'=>\Auth::user()->id
        ]));
        return response()->json([
            'status' => true
        ]);
    }
}

;

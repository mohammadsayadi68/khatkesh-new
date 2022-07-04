<?php

namespace App\Http\Controllers;

use App\RuleItemContent;
use Illuminate\Http\Request;

class RuleController extends Controller
{
    public function searchIn(Request $request, $id)
    {
        $this->validate($request, [
            'keyword' => 'required|string'
        ]);
        $keyword = $request->input('keyword');
        $items = RuleItemContent::search($keyword)
            ->whereRuleId($id)
            ->whereIn('name', [
                'ماده',
                'اصل'
            ])
            ->get();
        return response()->json([
            'count' => count($items),
            'result' => view('rule.search-in-rule.items',compact('items','keyword'))->render(),
            'ids'=>$items->pluck('id')->toArray()
        ]);

    }
}

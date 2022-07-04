<?php

namespace App\Http\Controllers\Api;

use App\Category;
use App\Clause;
use App\Common\RulesBot;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryRulesCollection;
use App\Http\Resources\CategoryRulesWithStructuresCollection;
use App\Http\Resources\OfflineRuleData;
use App\Http\Resources\OfflineRulesCollection;
use App\Http\Resources\RuleResource;
use App\Http\Resources\RuleStructureCollection;
use App\Http\Resources\SearchResultCollection;
use App\Http\Resources\SpecialRulesCollection;
use App\Rule;
use App\Sturcture;
use Illuminate\Http\Request;

class RuleController extends Controller
{
    static $offlineRules = [

        "2" => [
            83378,
            119146,
            83454,
            185683,
            111819,
            254248,
            254248,
            254248,
            83520,
            83309,
            83314,
            117844,
            180920,
            205259,
            205259,
            83496,
            83413,
            83408,
            83313
        ],
        "19" => [
            38162,
            38162,
            118374,
            255560,
            226095,
            230040,
            255555,
            118373,
            163968,
            242428,
            252995,
            190136,
            250936,
            118376,
            121403,
            86160,
            256609,
            124455,
            257344,
            255891,
            83473,
            83307,
            38175,
            38177,
            257240,
            84116
        ],
        "44" => [
            176127,
            83436,
            83380,
            83381,
            204955,
            83841,
            118160,
            83361,
            84651,
            83358,
            86032,
            83353,
            83610,
            85441,
            254237,
            123898,
            212862
        ],
        "61" => [
            112715,
            111881,
            83605,
            83646,
            84844,
            164768,
            245439,
            181055,
            111980,
            119638,
            176303,
            84805,
            85110,
            84610,
            98227,
            84452
        ],
        "77" => [
            198907,
            83564,
            226595,
            123407,
            254252,
            119637,
            184887,
            252907,
            184887,
            252907,
            184676,
            86055,
            243263,
            249938,
            177820,
            111639,
            83572,
            119506,
            83765,
            86196,
            222166,
            86194,
            164499,
            179450,
            180294,
            110438,
            197934,
            164796,
            261893,
            113200,
            179830,
            251753
        ],
        "107" => [
            178971,
            83430,
            83464,
            84139,
            84815,
            84208,
            84179,
            249864,
            83752,
            38183,
            123953,
            83447,
            83306,
            83657,
            83875,
            176856
        ],
        "123" => [
            187568,
            246563,
            87738,
            211417,
            84814,
            112444,
            112603,
            83931,
            86003
        ],
        "133" => [
            83899,
            85750,
            254781
        ],
        "135" => [
            83457,
            86054,
            180401,
            197421,
            83598,
            38174,
            249938,
            84813,
            86130,
            119637,
            123953,
            83306,
            84845,
            248208,
            111677,
        ],
        "150" => [
            116854,
            83372,
            120409,
            257899,
            83895
        ],
        "155" => [
            86055,
            83349,
            83767,
            243263,
            197012,
            86167,
            84226
        ],
        "162" => [
            83567,
            102247,
            264214,
            263631,
            164583,
            83515,
            102248,
            83699,
            83757,
            198260
        ]
    ];


    public function specialRules(Request $request)
    {
        $categories = $request->input('categories');
        $categories = Category::whereIn('id', $categories)->with('rules')->get();
        $user = \Auth::guard('api')->user();
        $rules = $rules = Rule::whereSpecial(1)->get();
        $bookmarkIds = [];
        $specialIds = [];

        if ($user) {
            $rulesUser = \Cache::remember('user-special-rules-' . $user->id, 30 * 24 * 60 * 60, function () use ($user) {
                return $user->specials;
            });
            $rules = $rules->merge($rulesUser)->unique('id');
            $bookmarkIds = \Cache::remember('bookmark-ids-' . $user->id, 30 * 24 * 60 * 60, function () use ($user) {
                return \DB::table('rule_bookmark')->where('user_id', $user->id)->get()->pluck('rule_id')->toArray();
            });
            $specialIds = \Cache::remember('user-special-rules-ids-' . $user->id, 30 * 24 * 60 * 60, function () use ($user) {
                return \DB::table('rule_special')->where('user_id', $user->id)->get()->pluck('rule_id')->toArray();
            });
        }

        return response()->json([
            'rules' => (new SpecialRulesCollection($rules))->resolve(),
            'categories' => (new CategoryRulesCollection($categories))->resolve(),
            'bookmarkIds' => $bookmarkIds,
            'specialIds' => $specialIds,
        ]);
    }

    public function single($id)
    {
        $rule = \Cache::remember('rule-' . $id, 30 * 24 * 60 * 60, function () use ($id) {
            return Rule::with(['seasons', 'clauses', 'notes', 'paragraphs'])->notHidden()->findOrFail($id);
        });
        $ruleData = (new RuleResource($rule))->resolve();
        $user = \Auth::guard('api')->user();
        $clauseBookmarkIds = [];
        if ($user)
            $clauseBookmarkIds = \Cache::remember('clause-bookmark-ids-' . $id, 30 * 24 * 60 * 60, function () use ($user) {
                return \DB::table('clause_bookmark')->where('user_id', $user->id)->get()->pluck('clause_id')->toArray();
            });
        return response()->json([
            'rule' => $ruleData,
            'clauseBookmarkIds' => $clauseBookmarkIds,
            'countClauses' => $rule->count_clauses
        ]);
    }

    public function singleData($id)
    {
        $rule = \Cache::remember('rule-data-' . $id, 30 * 24 * 60 * 60, function () use ($id) {
            return Rule::notHidden()->findOrFail($id);
        });
        $user = \Auth::guard('api')->user();

        $clauseBookmarkIds = [];
        if ($user)
            $clauseBookmarkIds = \Cache::remember('clause-bookmark-ids-' . $id, 30 * 24 * 60 * 60, function () use ($user) {
                return \DB::table('clause_bookmark')->where('user_id', $user->id)->get()->pluck('clause_id')->toArray();
            });
        return response()->json([
            'id' => $rule->id,
            'title' => $rule->title,
            'isSpecial' => $rule->is_special,
            'type' => $rule->typeName,
            'implementDate' => $rule->implement_date ? toJalali($rule->implement_date) : null,
            'approvalDate' => $rule->approval_date ? toJalali($rule->approval_date) : null,
            'approvalAuthority' => $rule->approval_authority,
            'clauseBookmarkIds' => $clauseBookmarkIds,
            'countClauses' => $rule->count_clauses
        ]);

    }

    public function singleStructure($id)
    {
        $rule = Rule::notHidden()->findOrFail($id);
        $structures = Sturcture::whereRuleId($id)->where('type', '!=', 'interpretation')->whereNull('parent_id')->with('structureable', 'childs')->orderBy('main_id');
        if (\request()->get('type') == 'all') {
            $structures = \Cache::remember('rule-structure-all-' . $id, 30 * 24 * 60 * 60, function () use ($structures) {
                return $structures->get();
            });
        } else
            $structures = $structures->paginate();
        return new RuleStructureCollection($structures);
    }

    public function search(Request $request)
    {
        $this->validate($request, [
            'category' => 'nullable|integer',
            'keyword' => 'nullable|string',
            'startDate' => 'required|digits:4',
            'endDate' => 'required|digits:4',
        ]);
        $category = $request->input('category');
        $keyword = $request->input('keyword');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $startDate = \Morilog\Jalali\CalendarUtils::toGregorian($startDate, 1, 1)[0];
        $endDate = \Morilog\Jalali\CalendarUtils::toGregorian($endDate, 1, 1)[0];
        if (!$category and !$keyword and !$startDate and !$endDate)
            return response()->json([
                'status' => false
            ], 405);
        $rules = Rule::query()
            ->notHidden()
            ->select(['rules.*']);
        if ($startDate)
            $rules = $rules->whereYear('rules.approval_date', '>=', $startDate);
        if ($endDate)
            $rules = $rules->whereYear('rules.approval_date', '<=', $endDate);
        if ($keyword)
            $rules = $rules->search($keyword, ['title', 'text']);
        if ($category)
            $rules = $rules->where('rules.category_rule_id', $category);
        $rules = $rules->orderByDesc('rules.approval_date')->get();
        return new SearchResultCollection($rules);


    }

    public function bookmark(Request $request)
    {
        $this->validate($request, [
            'rule' => 'required|integer'
        ]);
        $user = \Auth::user();
        $rule = Rule::findOrFail($request->input('rule'));
        $type = true;
        if ($rule->is_bookmarked) {
            $rule->bookmarks()->detach($user->id);
            $type = false;
        } else {
            $rule->bookmarks()->attach($user->id);
        }
        \Cache::forget('bookmark-ids-' . $user->id);
        $ids = \DB::table('rule_bookmark')->where('user_id', $user->id)->get()->pluck('rule_id')->toArray();
        return response()->json([
            'type' => $type,
            'ids' => $ids
        ]);

    }

    public function clauseBookmark(Request $request)
    {
        $this->validate($request, [
            'clause' => 'required|integer',
            'rule' => 'required|integer'
        ]);
        $user = \Auth::user();
        $ruleId = $request->input('rule');
        $clause = Clause::findOrFail($request->input('clause'));
        $type = true;
        if ($clause->is_bookmarked) {
            $clause->bookmarks()->detach($user->id);
            $type = false;
        } else {
            $clause->bookmarks()->attach([$user->id => [
                'rule_id' => $ruleId
            ]]);
        }
        \Cache::forget('clause-bookmark-ids-' . $user->id);
        $ids = \DB::table('clause_bookmark')->where('user_id', $user->id)->get()->pluck('clause_id')->toArray();
        return response()->json([
            'type' => $type,
            'ids' => $ids
        ]);

    }

    public function special(Request $request)
    {
        $this->validate($request, [
            'rule' => 'required|integer'
        ]);
        $userId = \Auth::id();
        $rule = Rule::findOrFail($request->input('rule'));
        $type = true;
        if ($rule->is_special) {
            $rule->specials()->detach($userId);
            $type = false;
        } else {
            $rule->specials()->attach($userId);
        }
        \Cache::forget('user-special-rules-' . $userId);
        \Cache::forget('user-special-rules-ids-' . $userId);
        $specialIds = \Cache::remember('user-special-rules-ids-' . $userId, 30 * 24 * 60 * 60, function () use ($userId) {
            return \DB::table('rule_special')->where('user_id', $userId)->get()->pluck('rule_id')->toArray();
        });
        return response()->json([
            'type' => $type,
            'specialIds' => $specialIds
        ]);

    }

    public function searchMain(Request $request)
    {
        $this->validate($request, [
            'rule' => 'required|integer',
            'keyword' => 'nullable|string'
        ]);
        $id = $request->input('rule');
        $structures = Sturcture::whereRuleId($id)->where('type', '!=', 'interpretation')->whereNull('parent_id')->with('structureable', 'childs')->orderBy('main_id')->get();
        $ruleResponse = (new RuleStructureCollection($structures))->resolve();
        $rule = json_encode($ruleResponse);
        $count_keys = preg_match_all('/search/', $rule);
        return response()->json([
            'rule' => $ruleResponse,
            'count' => $count_keys
        ]);
    }

    public function categoriesRuleIds(Request $request)
    {
        $this->validate($request, [
            'categories.*' => 'required|numeric'
        ]);
        $categories = $request->input('categories', []);
        $rules = Rule::select('id')->whereIn('category_id', $categories)->get()->pluck('id')->toArray();
        return response()->json([
            'ids' => $rules
        ]);

    }

    public function downloadCategory($categoryId)
    {
        $rules = Rule::whereCategoryId($categoryId)->get();
        return new CategoryRulesWithStructuresCollection($rules);
    }

    public function offlineRulesList()
    {
        $response = [];
        $rules = [
            14810
        ];
        $response["rules"] = (new SearchResultCollection(Rule::whereIn('id', $rules)->get()))->resolve();

//        $path = public_path('/practicalRules.json');
//        \File::put($path,json_encode($response,JSON_UNESCAPED_UNICODE));
        return $response;

    }

    public function offlineRulesData()
    {
        foreach (self::$offlineRules as $id => $ids) {
            $ids = self::$offlineRules[$id];
            $dir = public_path('/offline-rules/' . $id);
            if (!\File::exists($dir))
                \File::makeDirectory($dir, 493, true);
            foreach ($ids as $ruleId) {
                $rule = Rule::whereMainId($ruleId)->with(['strurctures' => function ($query) {
                    $query->where('type', '!=', 'interpretation')->whereNull('parent_id')->with('structureable', 'childs');
                }])->first();
                if (!$rule) {
                    RulesBot::getInstance()->storeWithIds([$ruleId]);
                    $rule = Rule::whereMainId($ruleId)->with(['strurctures' => function ($query) {
                        $query->where('type', '!=', 'interpretation')->whereNull('parent_id')->with('structureable', 'childs');
                    }])->first();
                }
                $data = json_encode((new OfflineRuleData($rule))->resolve(), JSON_UNESCAPED_UNICODE);
                \File::put($dir . '/' . $rule->id . '.json', $data);
            }


        }
    }

    public function storeOfflineRules()
    {
        $rulesBot = RulesBot::getInstance();
        foreach (self::$offlineRules as $id => $ids) {
            $ids = self::$offlineRules[$id];
            $rulesBot->storeWithIds($ids);
        }
    }
}

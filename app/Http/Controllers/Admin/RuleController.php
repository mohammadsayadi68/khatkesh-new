<?php

namespace App\Http\Controllers\Admin;

use App\CategoryRule;
use App\Chapter;
use App\Common\RulesBot;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRuleRequest;
use App\Jobs\StoreRules;
use App\Rule;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;

class RuleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $title = 'مدیریت قوانین';
        $rules = Rule::latest()->paginate();
        return view('admin.rule.index', compact('title', 'rules'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $title = 'افزودن قانون';
        $categories = CategoryRule::all();
        return view('admin.rule.create', compact('title', 'categories'));
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
           'title'=>'required|string',
           'implement-date'=>'required|date_format:Y/m/d',
           'approval-date'=>'required|date_format:Y/m/d',
           'approval-authority'=>'required|string',
           'type'=>'required|integer|min:1|max:3'
        ]);
        $rule = new Rule();
        $rule->title = $request->input('title');
        $rule->implement_date = toGregorian($request->input('implement-date'));
        $rule->approval_date = toGregorian($request->input('approval-date'));
        $rule->approval_authority = $request->input('approval-authority');
        $rule->type = $request->input('type');
        $rule->save();
        return redirect(route('admin.rule.edit',$rule->id));
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
    	$title = 'ویرایش قانون';
			$rule = Rule::findOrFail($id);
			$ruleId = $id;
			$structures = Sturcture::whereRuleId($id)->whereNull('parent_id')->with('structureable', 'childs')->get();
			return view('admin.rule.edit', compact('title', 'rule', 'ruleId', 'structures'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {

    	$id = $request->input('id');
			$body = $request->input('body');

			$structure = Sturcture::findOrFail($id);
			$structure->structureable->text = $body;
			$structure->structureable->save();

			return response()->json(['message' => 'تغییرات با موفقیت اعمال شد.', 'status' => true]);


//        $rule = Rule::findOrFail($id);
//        $title = $request->input('title');
//        $approvalDate = $request->input('approval-date') ? toGregorian($request->input('approval-date')) : null;
//        $implementDate = $request->input('implement-date') ? toGregorian($request->input('implement-date')) : null;
//        $category = $request->input('category');
//        $approvalAuthority = $request->input('approval-authority');
//        $status = $request->input('status');
//        $type = $request->input('type');
//        $introduction = $request->input('introduction');
//        $text = $request->input('text');
//        $price = $request->input('price');
//        $signature = $request->input('signature');
//        $rule->title = $title;
//        $rule->approval_date = $approvalDate;
//        $rule->implement_date = $implementDate;
//        $rule->category_rule_id = $category;
//        $rule->approval_authority = $approvalAuthority;
//        $rule->status = $status;
//        $rule->type = $type;
//        $rule->introduction = $introduction;
//        $rule->text = $text;
//        $rule->price = $price;
//        $rule->signature = $signature;
//        $rule->save();
//        $clauses = $request->input('clauses', []);
//        $currentClauses = $rule->clauses()->get()->pluck('id')->toArray();
//        $currentNotes = Note::whereIn('clause_id', $currentClauses)->get()->pluck('id')->toArray();
//        $currentParagraphs = Paragraph::whereIn('clause_id', $currentClauses)->get()->pluck('id')->toArray();
//        $updatedClauses = [];
//        $updatedNotes = [];
//        $updatedParagraphs = [];
//        foreach ($clauses as $clauseData) {
//            $id = $clauseData['id'] ?? '';
//            if ($id) {
//                $updatedClauses[] = $id;
//                $clause = Clause::find($id);
//            } else
//                $clause = new Clause();
//            $clause->number = $clauseData['number'];
//            $clause->text = $clauseData['text'];
//            $clause->rule_id = $rule->id;
//            $clause->save();
//            $notes = $clauseData['notes'] ?? [];
//            $paragraphs = $clauseData['paragraphs'] ?? [];
//            foreach ($paragraphs as $paragraphData) {
//                $paragraphId = $paragraphData['id'] ?? '';
//                if ($paragraphId) {
//                    $updatedParagraphs[] = $paragraphId;
//                    $paragraph = Paragraph::find($paragraphId);
//
//                } else
//                    $paragraph = new Paragraph();
//                $paragraph->number = $paragraphData['number'];
//                $paragraph->text = $paragraphData['text'];
//                $paragraph->clause_id = $clause->id;
//                $paragraph->save();
//            }
//            foreach ($notes as $noteData) {
//                $noteId = $noteData['id'] ?? '';
//                if ($noteId) {
//                    $updatedNotes[] = $noteId;
//                    $note = Note::find($noteId);
//                } else
//                    $note = new Note();
//                $note->number = $noteData['number'];
//                $note->text = $noteData['text'];
//                $note->clause_id = $clause->id;
//                $note->save();
//
//            }
//        }
//        Paragraph::whereIn('id', getDiffrenceBetweenArrays($currentParagraphs, $updatedParagraphs))->delete();
//        Note::whereIn('id', getDiffrenceBetweenArrays($currentNotes, $updatedNotes))->delete();
//        Clause::whereIn('id', getDiffrenceBetweenArrays($currentClauses, $updatedClauses))->delete();
//        message('قانون با موفقیت بروزرسانی شد', 'success');
//        return redirect(route('admin.rule.index'));


    }


    public function addToRule(Request $request) {
    	$id = $request->input('id');
			$type = $request->input('type');
			$newCase = null;
			$text = $request->input('text');
			$number = $request->input('number');
			$name = $request->input('name');
			$ruleId = $request->input('rule_id');
			$isRule = $request->input('is_rule') === "true" ? true : false;
			if (!$isRule){
				$structure = Sturcture::findOrFail($id);
				$structureable = $structure->structureable;
			}else{
				$structureable = Rule::find($ruleId);
			}


			switch ($type){
				case "clause":
					$newCase = new Clause();
					$newCase->text = replace_enter_with_br($text);
					$newCase->number = $number;
					$newCase->clauseable()->associate($structureable);
					$newCase->main_id = 0;
					$newCase->save();
					break;

				case "note":
					$newCase = new Note();
					$newCase->text = replace_enter_with_br($text);
					$newCase->number = $number;
					$newCase->noteable()->associate($structureable);
					$newCase->main_id = 0;
					$newCase->save();
					break;

				case "paragraph":
					$newCase = new Paragraph();
					$newCase->text = replace_enter_with_br($text);
					$newCase->number = $number;
					$newCase->paragraphable()->associate($structureable);
					$newCase->main_id = 0;
					$newCase->save();
					break;

				case "session":
					$newCase = new Season();
					$newCase->number = $number;
					$newCase->name = $name;
					$newCase->seasonable()->associate($structureable);
					$newCase->main_id = 0;
					$newCase->save();
					break;

				case "topic":
					$newCase = new Topic();
					$newCase->number = $number;
					$newCase->name = $name;
					$newCase->topicable()->associate($structureable);
					$newCase->main_id = 0;
					$newCase->save();
					break;

				case "section":
					$newCase = new Section();
					$newCase->number = $number;
					$newCase->name = $name;
					$newCase->sectionable()->associate($structureable);
					$newCase->main_id = 0;
					$newCase->save();
					break;

				case "cover":
					$newCase = new Cover();
					$newCase->number = $number;
					$newCase->name = $name;
					$newCase->coverable()->associate($structureable);
					$newCase->main_id = 0;
					$newCase->save();
					break;

				case "book":
					$newCase = new Book();
					$newCase->number = $number;
					$newCase->name = $name;
					$newCase->bookable()->associate($structureable);
					$newCase->main_id = 0;
					$newCase->save();
					break;

				case "chapter":
					$newCase = new Chapter();
					$newCase->number = $number;
					$newCase->name = $name;
					$newCase->chapterable()->associate($structureable);
					$newCase->main_id = 0;
					$newCase->save();
					break;

				case "episode":
					$newCase = new Episode();
					$newCase->number = $number;
					$newCase->name = $name;
					$newCase->episodeable()->associate($structureable);
					$newCase->main_id = 0;
					$newCase->save();
					break;
			}

				$newStructure = new Sturcture();
			if($isRule) {
				$newStructure->rule_id = $structureable->id;
				$newStructure->parent_id = null;
			} else {
				$newStructure->rule_id = $structure->rule_id;
				$newStructure->parent_id = $structure->id;
			}
			$newStructure->structureable()->associate($newCase);
			$newStructure->type = $type;
			$newStructure->main_id = 0;
			$newStructure->save();



			$html = view('admin.rule.item')->with(['structures' => [$newStructure]]);

			return $html;

		}


	public function deleteItem(Request $request)
	{
		$id = $request->input('id');
		$structure = Sturcture::findOrFail($id);
		$structure->delete();
		$structure->childs()->delete();
		return response()->json(['status' => 'sucess', 'message' => "آیتم موردنظر حذف شد."]);
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


    public function filter(Request $request)
    {
        parse_str($request->input('data'), $data);
        $page = $data['page'];
        $sortColumn = $data['sort-column'];
        $sortType = $data['sort-type'];
        $title = $data['title'] ?? '';
        $status = $data['status'] ?? '';
        $type = $data['type'] ?? [];
        $minCountClauses = convert2english($data['min-count-clauses']);
        $maxCountClauses = convert2english($data['max-count-clauses']);
        $minApprovalDate = convert2english($data['min-approval-date']) ?? '';
        $maxApprovalDate = convert2english($data['max-approval-date']) ?? '';
        $minPrice = convert2english($data['min-price']);
        $maxPrice = convert2english($data['max-price']);

        $minCountDownloads = convert2english($data['min-count-downloads']);
        $maxCountDownloads = convert2english($data['max-count-downloads']);
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $rules = Rule::query()->orderBy($sortColumn, $sortType);
        if ($title)
            $rules = $rules->search($title);
        if ($minPrice != '')
            $rules = $rules->where('price', '>=', $minPrice);
        if ($maxPrice != '')
            $rules = $rules->where('price', '<=', $maxPrice);

        if (count($type))
            $rules = $rules->whereIn('type', $type);
        if ($status != '')
            $rules = $rules->where('status', $status);

        if ($maxCountClauses)
            $rules = $rules->has('clauses', '<=', $maxCountClauses);
        if ($minCountClauses)
            $rules = $rules->has('clauses', '>=', $minCountClauses);

        if ($maxCountDownloads)
            $rules = $rules->where('count_downloads', '<=', $maxCountDownloads);
        if ($minCountDownloads)
            $rules = $rules->where('count_downloads', '>=', $minCountDownloads);


        if ($maxApprovalDate) {
            $maxApprovalDate = toGregorian($maxApprovalDate);
            $rules = $rules->whereDate('approval_date', '<=', $maxApprovalDate);
        }

        if ($minApprovalDate) {
            $minApprovalDate = toGregorian($minApprovalDate);
            $rules = $rules->whereDate('approval_date', '>=', $minApprovalDate);
        }


        $rules = $rules->paginate();
        return response()->view('admin.rule.rules-partial', compact('rules'));
    }

    public function viewFetchRulesWithQavaninCategoryId()
    {
        $title = 'دریافت قوانین';
        $categories = CategoryRule::whereNotNull('resource_id')->get();
        return view('admin.rule.store-rules-category', compact('title', 'categories'));
    }

    public function fetchRulesWithQavaninCategoryId(Request $request)
    {
        $this->validate($request, [
            'fetch-type' => 'required|numeric|in:1,2',
            'category' => 'required|numeric|exists:category_rules,id',
        ]);
        $backgroundProcess = (integer)($request->input('background-process') === 'on');
        $category = CategoryRule::findOrFail($request->input('category'));
        $fetchType = $request->input('fetch-type');
        switch ($fetchType) {
            case 1:
                if ($backgroundProcess) {
                    $job = new StoreRules($category);
                    dispatch($job)->delay(now()->addMinute());
                    message('عملایت ذخیره سازی با موفقیت اجرا شد.', 'success');
                } else {
                    RulesBot::getInstance()->storeWithCategory($category);
                    message('قوانین با موفقیت ذخیره شدند', 'success');
                }
                return redirect(route('admin.rule.index'));
            case 2:
                $data = RulesBot::getInstance()->showTitleAndIds($category);
                $title = 'پیش نمایش ذخیره قوانین';
                return view('admin.rule.fetch-rules-preview', compact('title', 'data', 'category', 'backgroundProcess'));

        }
    }

    public function storeRulesPreview(Request $request)
    {
        $this->validate($request, [
            'ids.*' => 'required|numeric',
            'category' => 'required|numeric|exists:category_rules,id',
            'background-process' => 'required|in:0,1'
        ]);
        $category = CategoryRule::findOrFail($request->input('category'));
        $backgroundProcess = $request->input('background-process');
        $ids = $request->input('ids');
        if ($backgroundProcess) {
            $job = new StoreRules($category, $ids);
            dispatch($job)->delay(now()->addMinute());
            message('عملایت ذخیره سازی با موفقیت اجرا شد.', 'success');
        } else {
            $rulesBotInstance = RulesBot::getInstance();
            $rulesBotInstance->setCategory($category);
            $rulesBotInstance->storeWithIds($ids);
            message('قوانین انتخاب شده با موفقیت ذخیره شدند', 'success');
        }

        return redirect(route('admin.rule.index'));
    }

    public function updateFromResource($id)
    {
        $rule = Rule::findOrFail($id);
        $category = $rule->category;
        $rulesBotInstance = RulesBot::getInstance();
        $rulesBotInstance->setCategory($category);
        $rulesBotInstance->storeRule($category->resource_id);
        message('قانون با موفقیت بروزرسانی شد', 'success');
        return back();
    }

    public function toggleSpecial(Request $request)
    {
        $this->validate($request, [
            'rule' => 'required|numeric'
        ]);
        $rule = Rule::findOrFail($request->input('rule'));
        $rule->special = !$rule->special;
        $rule->save();
        \Cache::forget('main-special-rules');
        return response()->json([
            'isSpecial' => (boolean)$rule->special
        ]);
    }

    public function mainEdit($id)
    {
        $rule = Rule::findOrFail($id);
        $title = 'ویرایش';
        return view('admin.rule.main-edit',compact('rule','title'));
    }

    public function mainUpdate(Request $request,$id)
    {
        $rule = Rule::findOrFail($id);
        $this->validate($request,[
           'selected'=>'required|integer'
        ]);
        $rule->selected_constitution = 0;
        $rule->selected_criminal = 0;
        $rule->selected_legal= 0;
        switch ($request->input('selected')){
            case "1":
                $rule->selected_constitution = 1;
                break;
            case "2":
                $rule->selected_criminal = 1;
                break;
            case "3":
                $rule->selected_legal = 1;
                break;

        }
        $rule->save();
        return back();
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\CategoryRule;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Validation\ValidationException;

class CategoryRuleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $title = 'لیست مراجع تصویب';
        $categoryRules = CategoryRule::latest()->withCount('rules')->paginate();
        return view('admin.approval-authority.index', compact('title', 'categoryRules'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $title = 'افزودن مرجع تصویب';
        return view('admin.approval-authority.create', compact('title'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'title' => 'required|string',
            'resource_id' => 'nullable|numeric'
        ]);
        CategoryRule::create($request->all(['title', 'resource_id']));
        message('مرجع تصویب جدید با موفقیت اضافه شد', 'success');
        \Cache::forget('approval-authorities');
        return redirect(route('admin.category-rule.index'));
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
        $category = CategoryRule::findOrFail($id);
        $title = 'ویرایش مرجع تصویب';
        return view('admin.approval-authority.edit', compact('title', 'category'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $id)
    {
        $category = CategoryRule::findOrFail($id);
        $this->validate($request, [
            'title' => 'required|string',
            'resource_id' => 'nullable|numeric'
        ]);
        $category->update($request->all('title','resource_id'));
        \Cache::forget('approval-authorities');
        message('مرجع تصویب با موفقیت بروزرسانی شد', 'success');
        return redirect(route('admin.category-rule.index'));

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
            'categories' => 'required|array',
            'categories.*' => 'required|numeric',
        ]);
        CategoryRule::whereIn('id', $request->input('categories'))->delete();
        return response()->json(true);
    }

    /**
     * @param Request $request
     * @throws \Illuminate\Validation\ValidationException
     */
    public function paradeEnable(Request $request)
    {
        $this->validate($request, [
            'categories' => 'required|array',
            'categories.*' => 'required|numeric',
        ]);
        CategoryRule::whereIn('id', $request->input('categories'))->update(['enabled' => 1]);
        \Cache::forget('categories');
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
            'categories' => 'required|array',
            'categories.*' => 'required|numeric',
        ]);
        CategoryRule::whereIn('id', $request->input('categories'))->update(['enabled' => 0]);
        \Cache::forget('categories');
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
            'sort-column' => 'required|string|in:title,enabled,rules_count,id',
            'page' => 'required|numeric'
        ]);
        if ($validator->fails())
            throw new ValidationException($validator);
        $sortColumn = $data['sort-column'];
        $sortType = $data['sort-type'];
        $page = $data['page'];
        $title = $data['title'];
        $status = $data['status'];
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $categoryRules = CategoryRule::orderBy($sortColumn, $sortType)->withCount('rules');
        if ($title)
            $categoryRules = $categoryRules->where('title', 'LIKE', '%' . $title . '%');
        if ($status != '')
            $categoryRules = $categoryRules->whereEnabled($status);
        $categoryRules = $categoryRules->paginate();
        return response()->view('admin.approval-authority.categories-partial', compact('categoryRules'));


    }
}

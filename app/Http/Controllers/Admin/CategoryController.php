<?php

namespace App\Http\Controllers\Admin;

use App\Category;
use App\Http\Controllers\Controller;
use App\Rule;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $title = 'لیست مراجع تصویب';
        $categoryRules = Category::latest()->withCount('rules')->paginate();
        return view('admin.category.index', compact('title', 'categoryRules'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $title = 'افزودن دسته بندی';
        return view('admin.category.create', compact('title'));
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
            'rules.*'=>'required|numeric',
        ]);
        $category = Category::create($request->all(['title']));
        $rulesId = $request->input('rules');
        Rule::whereIn('id',$rulesId)->update([
           'category_id'=>$category->id
        ]);
        message('دسته بندی جدید با موفقیت اضافه شد', 'success');
        \Cache::forget('base-categories');
        return redirect(route('admin.category.index'));
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
        $category = Category::findOrFail($id);
        $title = 'ویرایش دسته بندی';
        return view('admin.category.edit', compact('title', 'category'));
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
        $category = Category::findOrFail($id);
        $this->validate($request, [
            'title' => 'required|string',
        ]);
        $category->update($request->all('title'));
        \Cache::forget('base-categories');
        $rulesId = $request->input('rules');
        Rule::where('category_id',$category->id)->update([
            'category_id'=>null
        ]);
        Rule::whereIn('id',$rulesId)->update([
            'category_id'=>$category->id
        ]);
        message('دسته بندی با موفقیت بروزرسانی شد', 'success');
        return redirect(route('admin.category.index'));

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
        Category::whereIn('id', $request->input('categories'))->delete();
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
        Category::whereIn('id', $request->input('categories'))->update(['enabled' => 1]);
        \Cache::forget('base-categories');
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
        Category::whereIn('id', $request->input('categories'))->update(['enabled' => 0]);
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
        $categoryRules = Category::orderBy($sortColumn, $sortType)->withCount('rules');
        if ($title)
            $categoryRules = $categoryRules->where('title', 'LIKE', '%' . $title . '%');
        if ($status != '')
            $categoryRules = $categoryRules->whereEnabled($status);
        $categoryRules = $categoryRules->paginate();
        return response()->view('admin.category.categories-partial', compact('categoryRules'));


    }

    public function rulesSearch(Request $request)
    {
        $this->validate($request,[
           'keyword'=>'required|string'
        ]);
        $keyword = $request->input('keyword');
        $rules = Rule::where('title','LIKE','%'.$keyword.'%')->get();
        return response()->view('admin.category.rules-search-result',compact('rules'));
    }
}

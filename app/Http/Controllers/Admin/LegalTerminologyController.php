<?php

namespace App\Http\Controllers\Admin;

use App\LegalTerminology;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LegalTerminologyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $legalTerminologies = LegalTerminology::query()->latest()
            ->paginate();
        $title = 'ترمینولوژی حقوقی';
        return view('admin.legal-terminology.index',compact('title','legalTerminologies'));


    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $title = 'افزودن در ترمینولوژی حقوقی';
        return view('admin.legal-terminology.create',compact('title'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request,[
            'name'=>'required|string|unique:legal_terminologies',
            'description'=>'required|string',
        ]);
        LegalTerminology::query()->create([
            'name'=>$request->input('name'),
            'description'=>$request->input('description'),
        ]);
        return redirect(route('admin.legal-terminology.index'));
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $legalTerminology = LegalTerminology::findOrFail($id);
        $title = 'ویرایش در ترمینولوژی حقوقی';
        return view('admin.legal-terminology.edit',compact('title','legalTerminology'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $legalTerminology = LegalTerminology::findOrFail($id);
        $this->validate($request,[
            'name'=>'required|string|unique:legal_terminologies,name,'.$legalTerminology->id,
            'description'=>'required|string',
        ]);
        $legalTerminology->update([
            'name'=>$request->input('name'),
            'description'=>$request->input('description'),
        ]);
        return redirect(route('admin.legal-terminology.index'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StructureNoteCollection;
use App\Http\Resources\StructureNoteResource;
use App\StructureNote;
use App\Sturcture;
use Illuminate\Http\Request;

class StructureNoteController extends Controller
{
    public function getList()
    {
        $user = \Auth::user();
        $structureNotes = StructureNote::whereUserId($user->id)->get()->groupBy('structure_id');
        $response = [];

        foreach ($structureNotes as $structureId=>$notes){
            $response[$structureId] = new StructureNoteResource($notes[0]);
        }
        return $response;
   }

    public function store(Request $request)
    {
        $this->validate($request, [
            'structureId' => 'required|numeric',
            'note' => 'required|string',
        ]);
        $structureId = $request->input('structureId');
        Sturcture::whereType('clause')->findOrFail($structureId);
        $note = $request->input('note');
        $user = \Auth::user();
        $oldNoteRow = StructureNote::whereStructureId($structureId)->whereUserId($user->id)->first();
        if ($oldNoteRow)
            return response()->json([
                'message' => 'Clause already has note',
            ], 405);

        $SNote = StructureNote::create([
            'note' => $note,
            'structure_id' => $structureId,
            'user_id' => $user->id
        ]);
        return new StructureNoteResource($SNote);
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'structureNoteId' => 'required|numeric',
            'note'=>'required|string'
        ]);
        $user = \Auth::user();
        $structureNote = StructureNote::whereUserId($user->id)->findOrFail($request->input('structureNoteId'));
        $structureNote->note = $request->input('note');
        $structureNote->save();
        return  new StructureNoteResource($structureNote);
    }

    public function delete(Request $request)
    {
        $this->validate($request, [
            'structureNoteId' => 'required|numeric',
        ]);
        $user = \Auth::user();
        $structureNote = StructureNote::whereUserId($user->id)->findOrFail($request->input('structureNoteId'));
        $structureNote->delete();
        return response()->json([
            'message'=>'Structure Note successfully deleted',
        ]);
    }
}

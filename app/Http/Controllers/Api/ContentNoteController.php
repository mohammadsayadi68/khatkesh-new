<?php

namespace App\Http\Controllers\Api;

use App\ContentNote;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContentNoteResource;
use App\Http\Resources\StructureNoteResource;
use App\Question;
use App\StructureNote;
use App\Sturcture;
use Illuminate\Http\Request;

class ContentNoteController extends Controller
{
    public function getList()
    {
        $user = \Auth::user();
        $notes = ContentNote::whereUserId($user->id)->get();
        $response = [];

        foreach ($notes as $note) {
            $response[$note->type.'-'.$note->id] = new StructureNoteResource($note);
        }
        return $response;
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'tpye' => 'required|string|in:question,structure',
            'targetId' => 'required|integer',
            'note' => 'required|string'
        ]);

        $targetId = $request->input('targetId');
        $type = $request->input('type');
        $noteText = $request->input('note');
        switch ($typ){
            case "question":
                $contentAbleClass = Question::class;
                break;
            case "structure":
                $contentAbleClass = Sturcture::class;
                break;
        }
        $contentAble = $contentAbleClass::findOrFail($targetId);
        $user = \Auth::user();
        $oldNoteRow = ContentNote::whereContentnoteableId($targetId)->whereContentnoteableType($contentAbleClass)->whereUserId($user->id)->first();
        if ($oldNoteRow)
            return response()->json([
                'message' => 'Your target already has note',
            ], 405);

        $note = new ContentNote();
        $note->note = $noteText;
        $note->type= $type;
        $note->user_id= $user->id;
        $note->contentnoteable()->associate($contentAble);
        $note->save();
        return new ContentNoteResource($SNote);
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'contentNoteId' => 'required|integer',
            'note' => 'required|string'
        ]);
        $user = \Auth::user();
        $structureNote = ContentNote::whereUserId($user->id)->findOrFail($request->input('contentNoteId'));
        $structureNote->note = $request->input('note');
        $structureNote->save();
        return new ContentNoteResource($structureNote);
    }

    public function delete(Request $request)
    {
        $this->validate($request, [
            'contentNoteId' => 'required|numeric',
        ]);
        $user = \Auth::user();
        $structureNote = ContentNote::whereUserId($user->id)->findOrFail($request->input('contentNoteId'));
        $structureNote->delete();
        return response()->json([
            'message' => ' Note successfully deleted',
        ]);
    }
}

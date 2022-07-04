<?php

namespace App\Http\Controllers\Api;

use App\Clause;
use App\ContentReport;
use App\Exam;
use App\Http\Controllers\Controller;
use App\Question;
use App\Sturcture;
use Illuminate\Http\Request;

class ContentReportController extends Controller
{
    public function send(Request $request)
    {
        $this->validate($request, [
            'type' => 'required|string|in:exam,question,structure',
            'targetId' => 'required|integer',
            'description'=>'nullable|string'
        ]);
        $type = $request->input('type');
        $targetId = $request->input('targetId');
        switch ($type){
            case "question":
                $targetAbleClass = Question::class;
                break;
            case "exam":
                $targetAbleClass = Exam::class;
                break;
            case "structure":
                $targetAbleClass = Sturcture::class;
                break;

        }
        $targetAble = $targetAbleClass::findOrFail($targetId);
        $contentReport = new ContentReport();
        $contentReport->user_id  = \Auth::id();
        $contentReport->contentreportable()->associate($targetAble);
        $contentReport->description = $request->input('description');
        $contentReport->save();
        return response()->json([
           'message'=>'گزارش شما با موفقیت ثبت شد.'
        ]);
    }
}

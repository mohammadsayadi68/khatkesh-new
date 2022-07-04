<?php

namespace App\Http\Controllers\Api;

use App\CategoryExam;
use App\Exam;
use App\ExamPayment;
use App\ExamResult;
use App\ExamResultChoice;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Exam as ExamResource;
use App\Http\Resources\Api\ExamResult as ExamResultResource;
use App\Http\Resources\Api\ExamCategoryCollection;
use App\Http\Resources\Api\ExamCollection;
use App\Http\Resources\Api\ExamResultCollection;
use App\Http\Resources\Api\QuestionCollection;
use App\Question;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    // get list of exams of category
    public function categories(int $type)
    {
        $categories = CategoryExam::where('type', $type)->get();
        return new ExamCategoryCollection($categories);
    }

    // get list of exams of a category
    public function getExamsCategory(int $id)
    {
        $category = CategoryExam::findOrFail($id);
        $exams = $category->exams;
        return new ExamCollection($exams);
    }

    // get list of all exams
    public function list()
    {
        $exams = Exam::latest()->get();

        return new ExamCollection($exams);
    }

    // if user has attended in exam return questions with answers but if user not has attended show info of exam
    public function single(int $id)
    {
        $exam = Exam::findOrFail($id);
        return new ExamResource($exam);
    }

    // get questions and info of a exam
    public function download(int $id)
    {

//        $hasAccess = ExamPayment::where('exam_id', $id)->where('user_id', auth()->user()->id)->first();
//        if (!$hasAccess) {
//            return response()->json([
//                'data' => ['message' => "شما این آزمون را خریداری نکرده اید"],
//                'status' => false
//            ]);
//        }

        $exam = Exam::findOrFail($id);

        $examInfo = new ExamResource($exam);
        $questions = $exam->questions()->get();

        $questions = new QuestionCollection($questions);

        return response()->json([
            'info' => $examInfo,
            'questions' => $questions
        ]);
    }

    // store result with user's answers, time, etc... and return point and media of questions
    public function storeExamResult(Request $request, $examId)
    {
        $this->validate($request, [
            'answers' => 'required|array',
            'time' => 'required|numeric'
        ]);


        $answers = $request->input('answers');

        $time = $request->input('time');
        $result = 0;

        $examResult = ExamResult::create([
            'exam_id' => $examId,
            'user_id' => \Auth::user()->id,
            'time' => $time
        ]);

        foreach ($answers as $answer) {
            $examResultChoice = ExamResultChoice::create([
                'exam_result_id' => $examResult->id,
                'question_id' => $answer['questionId'],
                'user_answer' => $answer['answer']
            ]);
            $examResultChoice->user_answer === Question::findOrFail($answer['questionId'])->answer ? $result++ : null;
        }
        $examResult->result = $result;
        $examResult->save();
        $countQuestions = \Cache::remember('count-exam-questions-'.$examId,1000,function ()use($examId){
            return  Question::whereExamId($examId)->count();
        });
        return response()->json([
            'examResutlId' => $examResult->id,
            'result' => $result,
            'countQuestions' =>$countQuestions,
        ]);

    }

    // list of exams the user bought and hasAttended
    public function hasAttendedList()
    {
        $userId = auth()->user()->id;
        $exams = ExamResult::with('exam')->where('user_id', $userId)->get();
        return new ExamResultCollection($exams);
    }

    public function result($examResultId)
    {
        $userId = auth()->user()->id;
        $examResult = ExamResult::with(['exam'=>function($query){
            return $query->withCount('questions');
        }])->where('user_id', $userId)->findOrFail($examResultId);

        $examInfo = new ExamResultResource($examResult);
        $questions = (new QuestionCollection($examResult->exam->questions))->hasAttended(true,$examResultId);
        return response()->json([
            'info' => $examInfo,
            'questions' => $questions
        ]);
    }
}

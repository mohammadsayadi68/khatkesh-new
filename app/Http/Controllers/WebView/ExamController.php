<?php

namespace App\Http\Controllers\WebView;

use App\City;
use App\ExamFeedback;
use App\ExamResultChoice;
use App\Http\Controllers\Controller;
use App\Province;
use App\Question;
use http\Env\Response;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class ExamController extends Controller
{
    public function questions1397()
    {
        $agent = new Agent();

        $questions =  Question::all();
        $userAnswers =ExamResultChoice::where('user_id',\Auth::id())->get()->pluck('user_answer','question_id')->toArray();
        $choices = [
            'first' => 1,
            'second' => 2,
            'third' => 3,
            'fourth' => 4,
        ];
        $choices=  shuffle_assoc($choices);
        $questionsFeedback =[
            'کیفیت اطلاع رسانی آزمون',
            'کیفیت روند ثبت نام در آزمون',
            'تاریخ برگزاری آزمون',
            'میزان رضایت از مکان برگزاری آزمون',
            'میزان رضایت از نظم و ترکیب برگزاری آزمون',
            'کیفیت سوالات آزمون',
            'مناسب بودن زمان پاسخ دهی به سوالات',
            'میزان رضایت از عملکرد دانشگاه پیام‌نور در برگزاری آزمون',
            'میزان رضایت از عملکرد در برگزاری آزمون',
            'میزان رضایت از عملکرد خودتان در آزمون',
        ];
        $provinces = \Cache::remember('provinces',1111111,function (){
            return Province::all();
        });
            $isFeedbackStored =  ExamFeedback::whereUserId(\Auth::id())->first()!==null;
        return view('webview.test.questions', compact('questions','userAnswers','choices','questionsFeedback','provinces','isFeedbackStored'));
    }
    public function questionsTest1397()
    {
        $agent = new Agent();
        $device = $agent->device();
        $browser = $agent->browser();
//        if(!$agent->isAndroidOS() || $agent->isRobot() )
//            return '';
        $questions = Question::all();
        $userAnswers = ExamResultChoice::where('user_id',\Auth::id())->get()->pluck('user_answer','question_id')->toArray();
        $choices = [
            'first' => 1,
            'second' => 2,
            'third' => 3,
            'fourth' => 4,
        ];

        $choices = shuffle_assoc($choices);
        $questionsFeedback =[
            'کیفیت اطلاع رسانی آزمون',
            'کیفیت روند ثبت نام در آزمون',
            'تاریخ برگزاری آزمون',
            'میزان رضایت از مکان برگزاری آزمون',
            'میزان رضایت از نظم و ترکیب برگزاری آزمون',
            'کیفیت سوالات آزمون',
            'مناسب بودن زمان پاسخ دهی به سوالات',
            'میزان رضایت از عملکرد دانشگاه پیام‌نور در برگزاری آزمون',
            'میزان رضایت از عملکرد در برگزاری آزمون',
            'میزان رضایت از عملکرد خودتان در آزمون',
        ];
        $provinces = Province::all();
        $isFeedbackStored = ExamFeedback::whereUserId(\Auth::id())->first()!==null;
        return view('webview.test.questions', compact('questions','userAnswers','choices','questionsFeedback','provinces','isFeedbackStored'));
    }

    public function answer(Request $request)
    {
        $this->validate($request,[
            'question' => 'required|integer',
            'choice' => 'required|string'
        ]);
        $choices = [
            'first' => 1,
            'second' => 2,
            'third' => 3,
            'fourth' => 4,
        ];
        $question = Question::findOrFail($request->input('question'));
        ExamResultChoice::updateOrCreate([
            'user_id' => \Auth::id(),
            'question_id' => $request->input('question')
        ], [
            'user_answer' => $choices[$request->input('choice')]
        ]);
        \Cache::forget('user-answer-' . \Auth::id() . '-' . $request->input('question'));
        \Cache::forget('user-answer-'.$request->input('question').'-1');
        \Cache::forget('user-answer-'.$request->input('question').'-2');
        \Cache::forget('user-answer-'.$request->input('question').'-3');
        \Cache::forget('user-answer-'.$request->input('question').'-4');
        \Cache::forget('userAnswers-'.\Auth::id());
        return response([
            'status' => true,
            'answers_count'=>$question->answers_count ? $question->answers_count : 1,
            'count_answers_first'=>$question['count_answers_first'],
            'count_answers_second'=>$question['count_answers_second'],
            'count_answers_third'=>$question['count_answers_third'],
            'count_answers_fourth'=>$question['count_answers_fourth'],
        ]);
    }

    public function cities($provinceId)
    {
        $cities = City::whereProvinceId($provinceId)->get();
        return response()->view('webview.cities',compact('cities'));
    }

    public function storeFeedback(Request $request)
    {
        $this->validate($request,[
           'data'=>'required|string'
        ]);
        $dataInput = $request->input('data');
        parse_str($dataInput,$data);
        $validator = \Validator::make($data,[
           'description'=>'nullable|string',
           'province'=>'required|numeric',
           'city'=>'required|numeric',
        ]);
        if ($validator->fails())
            return $validator->errors();
        $userId = \Auth::id();
        $feedback = ExamFeedback::whereUserId($userId)->first();
        if ($feedback)
            return abort(404,'Not Found');
        $feedback = new ExamFeedback();
        $feedback->province_id = $data['province'];
        $feedback->city_id = $data['city'];
        $feedback->content = serialize($data);
        $feedback->user_id = $userId;
        $feedback->description= $data['description'];
        $feedback->save();
        \Cache::forget('isFeedbackStored-'.\Auth::id());
        return \response()->json(true);
    }
}

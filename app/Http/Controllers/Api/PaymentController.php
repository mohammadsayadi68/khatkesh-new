<?php

namespace App\Http\Controllers\Api;

use App\ExamPayment;
use App\Http\Controllers\Controller;
use App\Exam;
use App\Payment;
use App\User;
use App\UserService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    static $prices = [
        "user-vip" => 60000,
        "feghe" => 50000,
        "kanoon-vokala" => 60000
    ];

    public function check()
    {
        $user = \Auth::user();
        return response()->json([
            'vip' => $user->vip
        ]);
    }

    public function getInfo()
    {
        $user = \Auth::user();
        $price = 12000;
        if ($user->vip) {
            return response()->json([
                'message' => 'نسخه طلایی قبلا خریداری شده است'
            ]);
        }
        return response()->json([
            'price' => $price
        ]);
    }

    public function purchase(Request $request)
    {
        $this->validate($request, [
            'discount' => 'nullable|string',
            'exam_id' => 'required|numeric'
        ]);
        $exam = Exam::findOrFail($request->input('exam_id'));
        $price = $exam->price;
        $hasValidDiscount = has_valid_discount($request->input('discount'));
        $user = \Auth::user();
        if ($hasValidDiscount) {
            $price = $price * (100 - $hasValidDiscount->percent) / 100;
        }

        try {

            $gateway = \Gateway::zarinpal();

            $gateway->setCallback(url('/api/payment/exam-callback'));
            $gateway->setMobileNumber($user->phone);
            if ($price==0)
                $price=1000;
            $gateway
                ->price($price * 10)
                ->ready();

            $transID = $gateway->transactionId(); // شماره تراکنش
            $link = $gateway->redirect()->getTargetUrl();

            $payment = Payment::create([
                'link' => $link,
                'transaction_id' => $transID,
                'user_id' => $user->id,
                'type' => 'exma-'.$exam->id
            ]);


            if ($hasValidDiscount) {
                $payment->discount_id = $hasValidDiscount->id;
                $payment->save();
            }
            if ($hasValidDiscount->percent == 100) {
                \Cache::forget('user-' . \Auth::user()->id . 'is_bought_exam-' . $exam->id);
                ExamPayment::create([
                    'payment_id'=>$payment->id,
                    'user_id'=>\Auth::id(),
                    'exam_id'=>$exam->id
                ]);
                return response()->json([
                    'payed'=>true,
                ]);

            }
            $paymentLink = route('api.start-payment', $payment->id);
            return \response()->json([
                'status' => true,
                'paymentLink' => $paymentLink
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
                'status' => false
            ]);
        }

    }

    public function request(Request $request)
    {
        $this->validate($request, [
            'discount' => 'nullable|string'
        ]);
        $hasValidDiscount = has_valid_discount($request->input('discount'));
        $user = \Auth::user();
        $price = 12000;
        if ($hasValidDiscount) {
            if ($hasValidDiscount->percent == 100) {
                return $this->setUserVip($user);
            }
            $price = $price * (100 - $hasValidDiscount->percent) / 100;
        }
        if ($user->vip) {
            return response()->json([
                'message' => 'نسخه طلایی قبلا خریداری شده است',
                'status' => false
            ]);
        }
        try {

            $gateway = \Gateway::zarinpal();

            $gateway->setCallback(url('/api/payment/callback'));
            $gateway->setMobileNumber($user->phone);
            $gateway
                ->price($price * 10)
                ->ready();

            $refId = $gateway->refId(); // شماره ارجاع بانک
            $transID = $gateway->transactionId(); // شماره تراکنش

            // در اینجا
            //  شماره تراکنش  بانک را با توجه به نوع ساختار دیتابیس تان
            //  در جداول مورد نیاز و بسته به نیاز سیستم تان
            // ذخیره کنید .

            $link = $gateway->redirect()->getTargetUrl();

            $payment = Payment::create([
                'link' => $link,
                'transaction_id' => $transID,
                'user_id' => $user->id,
                'type' => 'user-vip'
            ]);
            if ($hasValidDiscount) {
                $payment->discount_id = $hasValidDiscount->id;
                $payment->save();
            }
            $paymentLink = route('api.start-payment', $payment->id);
            return \response()->json([
                'status' => true,
                'paymentLink' => $paymentLink
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
                'status' => false
            ]);
        }

    }

    public function startPayment($id)
    {
        $payment = Payment::findOrFail($id);
        return view('home.update-notice');
        return redirect($payment->link);
    }

    public function startPaymentService($id)
    {
        $payment = Payment::findOrFail($id);
        return redirect($payment->link);
    }

    public function callback()
    {
        try {

            $gateway = \Gateway::verify();
            $trackingCode = $gateway->trackingCode();
            $transID = $gateway->transactionId();
            $payment = Payment::where('transaction_id', $transID)->first();
            $payment->payed = 1;
            $payment->save();
            $user = $payment->user;
            if ($payment->discount_id and $payment->discount) {
                $discount = $payment->discount;
                $discount->increment('count_used');
                $discount->users()->attach($user->id);
                $discount->save();
            }
            $this->sendPaymentToClient($payment);
            return view('payback.success', compact('trackingCode'));
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return view('payback.fail', compact('message'));
        }
    }

    private function setUserVip(User $user)
    {
        $user->vip = 1;
        $user->save();
        $notificationKey = $user->notification_key;
        sendMessage([
            'key' => "2",
            'title' => 'حساب کاربری فعال شد',
            'body' => 'پرداخت با موفقیت انجام گردید و حساب کاربری شما فعال شد.'
        ], $notificationKey);
        return response()->json([
            'vip' => true,
        ]);
    }

    public function getPaymentInfo(Request $request)
    {
        $this->validate($request, [
            'type' => 'required|string|in:user-vip,feghe,kanoon-vokala'
        ]);
        $type = $request->input('type');
        $user = \Auth::user();
        $payment = Payment::whereType($type)->wherePayed(1)->whereUserId($user->id)->first();
        if ($payment)
            return response()->json([
                'payed' => true,
                'type' => $type
            ]);
        $price = self::$prices[$type];
        switch ($type) {
            case "user-vip":
                $name = "قوانین آفلاین دسته بندی شده";
                break;
            case "feghe":
                $name = "متون فقه";
                break;
            case "kanoon-vokala":
                $name = "قوانین کانون وکلا";
                break;
        }

        return response()->json([
            'payed' => false,
            'type' => $type,
            'price' => $price/10,
            'name' => $name
        ]);
    }

    public function getPaymentsPayedArrayOfUser()
    {
        $user = \Auth::user();
//        $services = UserService::whereUserId($user->id)->get()->pluck('type')->toArray();
        return response()->json([
            'services'=>[
                'user-vip',
                'feghe',
                'kanoon-vokala'
            ]
        ]);
    }

    public function sendPaymentRequest(Request $request)
    {
        $this->validate($request, [
            'discount' => 'nullable|string',
            'type' => 'required|string|in:user-vip,feghe,kanoon-vokala'
        ]);
        $type = $request->input('type');
        $user = \Auth::user();
        $payment = Payment::whereType($type)->wherePayed(1)->whereUserId($user->id)->first();
        if ($payment){
            return response()->json([
                'payed'=>true,
                'type'=>$type,
            ]);
        }
        $hasValidDiscount = has_valid_discount($request->input('discount'));
        $user = \Auth::user();
        $price = self::$prices[$type];
        if ($hasValidDiscount) {
            if ($hasValidDiscount->percent == 100) {
                $this->addServiceToUser($user,$type);
                return response()->json([
                    'payed'=>true,
                ]);

            }
            $price = $price * (100 - $hasValidDiscount->percent) / 100;
        }

        try {

            $gateway = \Gateway::zarinpal();

            $gateway->setCallback(url('/api/service-payment/callback'));
            $gateway->setMobileNumber($user->phone);
            $gateway
                ->price($price)
                ->ready();

            $refId = $gateway->refId(); // شماره ارجاع بانک
            $transID = $gateway->transactionId(); // شماره تراکنش

            // در اینجا
            //  شماره تراکنش  بانک را با توجه به نوع ساختار دیتابیس تان
            //  در جداول مورد نیاز و بسته به نیاز سیستم تان
            // ذخیره کنید .

            $link = $gateway->redirect()->getTargetUrl();

            $payment = Payment::create([
                'link' => $link,
                'transaction_id' => $transID,
                'user_id' => $user->id,
                'type' => $type
            ]);
            if ($hasValidDiscount) {
                $payment->discount_id = $hasValidDiscount->id;
                $payment->save();
            }
            $paymentLink = route('api.start-service-payment', $payment->id);
            return \response()->json([
                'status' => true,
                'paymentLink' => $paymentLink
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
                'status' => false
            ]);
        }

    }

    public function serviceCallback()
    {
        try {

            $gateway = \Gateway::verify();
            $trackingCode = $gateway->trackingCode();
            $transID = $gateway->transactionId();
            $payment = Payment::where('transaction_id', $transID)->first();
            $payment->payed = 1;
            $payment->save();
            $user = $payment->user;
            $this->addServiceToUser($user,$payment->type);
            if ($payment->discount_id and $payment->discount) {
                $discount = $payment->discount;
                $discount->increment('count_used');
                $discount->users()->attach($user->id);
                $discount->save();
            }
            $os = $user->os;
            $this->sendPaymentToClient($payment);
            $type = $payment->type;
            return view('payback.success', compact('trackingCode','os','type'));
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return view('payback.fail', compact('message'));
        }
    }
    public function examCallback()
    {
        try {

            $gateway = \Gateway::verify();
            $trackingCode = $gateway->trackingCode();
            $transID = $gateway->transactionId();
            $payment = Payment::where('transaction_id', $transID)->first();
            $payment->payed = 1;
            $payment->save();
            $user = $payment->user;
            if ($payment->discount_id and $payment->discount) {
                $discount = $payment->discount;
                $discount->increment('count_used');
                $discount->users()->attach($user->id);
                $discount->save();
            }
            $examId = str_replace('exam-','',$payment->type);
            ExamPayment::create([
                'payment_id'=>$payment->id,
                'user_id'=>$payment->user_id,
                'exam_id'=>$examId
            ]);

            $os = $user->os;
            $type = 'exam';
            $this->sendPaymentToClient($payment);
            return view('payback.success', compact('trackingCode','os','type'));
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return view('payback.fail', compact('message'));
        }
    }

    private function sendPaymentToClient(Payment $payment)
    {
        sendMessage([
            'key' => "3",
            'title' => 'پرداخت موفق!',
            'body' => 'پرداخت با موفقیت انجام گردید و سرویس مورد نظر فعال گردید.',
            "paymentType"=>$payment->type
        ], $payment->user->notification_key);
    }

    public function addServiceToUser(User $user,$type)
    {
        UserService::create([
            'user_id'=>$user->id,
            'type'=>$type
        ]);
    }


}

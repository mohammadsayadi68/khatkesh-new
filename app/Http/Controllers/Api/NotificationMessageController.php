<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationMessageCollection;
use App\NotificationMessage;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NotificationMessageController extends Controller
{
    public function messages()
    {
        $messages = NotificationMessage::leftJoin('notification_message_receiver', 'notification_message_receiver.notification_message_id', 'notification_messages.id')
            ->where(function ($query) {
                $query->where('notification_messages.target', 'ALL')
                    ->orWhere('notification_message_receiver.user_id', \Auth::id());
            })
            ->distinct()
            ->select('notification_messages.*')
            ->orderByDesc('notification_messages.id')
            ->get();
        $res =  new NotificationMessageCollection($messages);
        $user = \Auth::user();
        $ids = $user->messageViews->pluck('id')->toArray();
        return [
            'views'=>$ids,
            'messages'=>$res
        ];


    }

    public function testSendNotif($key,$id=1)
    {
        sendMessage([
            'key'=>"1",
            'title'=>'Test title',
            'message'=>'Test Message',
            'id'=>$id,
        ],$key);

    }

    public function view(Request $request)
    {
        try {
            $this->validate($request, [
                'id' => 'required|numeric'
            ]);
        } catch (ValidationException $e) {
            return response()->json($e->errors());
        }
        $user = \Auth::user();
        $id = $request->input('id');
        $ids = \DB::table('notification_message_view')->where('user_id', $user->id)->get()->pluck('notification_message_id')->toArray();
        if (!in_array($id, $ids))
            $ids[] = $id;
        $user->messageViews()->sync($ids);
        return response()->json(true);
    }

    public function viewedMessagesId()
    {
        $user = \Auth::user();
        return response()->json(['ids' => $user->messageViews->pluck('id')->toArray()]);
    }
}

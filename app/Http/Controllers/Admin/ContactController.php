<?php

namespace App\Http\Controllers\Admin;

use App\Contact;
use App\NotificationMessage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\Paginator;
use phpDocumentor\Reflection\Types\Null_;

class ContactController extends Controller
{
    public function index()
    {
        $title = "پیغام های دریافتی";
        return view('admin.contact.index')->with(['title' => $title]);
    }


    public function contactsList(Request $request)
    {

        $this->validate($request, [
            'page' => 'required|integer'
        ]);

        $page = $request->input('page');

        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $contacts = Contact::orderBy('id', 'DESC')->where('user_id', '!=', Null);
        $contacts = $contacts->get();
        $contacts = view('admin.contact.single-row-table', compact('contacts'));
        return $contacts;
    }


    public function sendResponse(Request $request)
    {

        $this->validate($request, [
            'subject' => 'required|string|min:2',
            'content' => 'required|string|min:4',
            'target' => 'required|string|in:ALL,CUSTOM',
            'contact_id' => 'nullable|numeric|exists:contacts,id',
            'user_id' => 'nullable|numeric|exists:users,id',

        ]);

        $target = $request->input('target');
        $userId = $request->input('user_id');
        if($target === 'CUSTOM') {
            $notificationMessage = NotificationMessage::create([
                'contact_id' => $request->input('contact_id'),
                'title' => $request->input('subject'),
                'message' => $request->input('content'),
                'target' => $target
            ]);
            $notificationMessage->receivers()->sync($userId);
        }

        return response()->json(['status' => true, 'message' => "پاسخ شما با موفقیت ارسال شد."]);

    }


    public function checked(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|numeric|exists:contacts,id',
        ]);

        $contact = Contact::findOrFail($request->input('id'));
        $contact->update([
            'checked_at' => date('Y-m-d H:i:s')
        ]);

        return response()->json(['status' => true, 'message' => 'پیغام دریافتی به بررسی شده تغییر یافت']);
    }
}

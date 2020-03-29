<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Provider;
use App\Models\Replay;
use App\Traits\Dashboard\PublicTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Dashboard\MessageTrait;
use Mail;
use App\Mail\NewAdminReplyMail;
use MercurySeries\Flashy\Flashy;
use Validator;
use DB;
use Auth;

class ProviderMessageController extends Controller
{
    use MessageTrait, PublicTrait;

    public function getDataTable()
    {
        try {
            return $this->getAllProviderMessages();
        } catch (\Exception $ex) {
            return view('admin.errors.404');
        }
    }

    public function index()
    {
        return view('message.provider.index');
    }

    public function view($id)
    {
        try {
            $message = $this->getMessageById($id);
            if ($message == null)
                return view('admin.errors.404');
            $message->replays()->update(['seen' => '1']);
            $replies = $message->replays()->get();
            return view('message.provider.view', compact('message', 'replies'));
        } catch (\Exception $ex) {
            return view('admin.errors.404');
        }
    }

    public function destroy($id)
    {
        try {
            $message = $this->getMessageById($id);
            if ($message == null)
                return view('admin.errors.404');

            $message->replays()->delete();
            $message->delete();
            Flashy::success('تم مسح الرسالة وردودها بنجاح');
            return redirect()->back();
        } catch (\Exception $ex) {
            return view('admin.errors.404');
        }
    }
    public function solvedMessage($id)
    {
        try {
            $message = $this->getMessageById($id);
            if ($message == null)
                return view('admin.errors.404');
             $message->update(['solved' => 1]);
            Flashy::success('تمت العملية بنجاح');
            return redirect()->back();
        } catch (\Exception $ex) {
            return view('admin.errors.404');
        }
    }

    public function reply(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "ticket_id" => "required",
                "message" => "required",
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput($request->all());
            }
            DB::beginTransaction();
            $message = $this->getMessageById($request->ticket_id);
            Replay::create([
                'message' => $request->message,
                'ticket_id' => $message->id,
                'FromUser' => 0,
            ]);

        if ($message->actor_type == 1) { // provider
            $provider = Provider::where('id', $message->actor_id)->first();
            $provider->makeVisible(['device_token', 'web_token']);
            (new \App\Http\Controllers\NotificationController(['title' => __('messages.New message reply'), 'body' => __('messages.You have new reply on your message')]))
                ->sendProvider($provider); // branch or provider
            (new \App\Http\Controllers\NotificationController(['title' => __('messages.New message reply'), 'body' => __('messages.You have new reply on your message')]))
                ->sendProviderWeb($provider,'','new_replay'); //branch or provider web
        }
            DB::commit();
            Flashy::success('تم حفظ الرد بنجاح');
            return redirect()->back();
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

}

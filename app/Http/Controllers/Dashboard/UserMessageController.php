<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Message;
use App\Models\Provider;
use App\Models\Replay;
use App\Models\Ticket;
use App\Models\User;
use App\Traits\Dashboard\MessageTrait;
use App\Traits\Dashboard\PublicTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Mail;
use App\Mail\NewAdminReplyMail;
use MercurySeries\Flashy\Flashy;
use Validator;
use DB;
use Auth;

class UserMessageController extends Controller
{
    use MessageTrait, PublicTrait;

    public function getDataTable()
    {
        try {
            return $this->getAllUserMessages();
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function index()
    {
        return view('message.user.index');
    }

    public function view($id)
    {
        try {
            $message = $this->getMessageById($id);
            if ($message == null)
                return view('admin.errors.404');
            $message->replays()->update(['seen' => '1']);
            $replies = $message->replays()->get();
            return view('message.user.view', compact('message', 'replies'));
        } catch (\Exception $ex) {
            return view('errors.404');
        }
    }

    public function destroy($id)
    {
        try {
            $message = $this->getMessageById($id);
            if ($message == null)
                return view('errors.404');

            $message->replays()->delete();
            $message->delete();
            Flashy::success('تم مسح الرسالة وردودها بنجاح');
            return redirect()->back();
        } catch (\Exception $ex) {
            return view('errors.404');
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
            $message = $this->getMessageById($request->ticket_id); //  ticket
            //$order = $this->getLastReplyOrder($message->id);
            Replay::create([
                'message' => $request->message,
                'ticket_id' => $message->id,
                'FromUser' => 0,
            ]);
            //$user = $this->getUser($message->user_id);
            //$appData = $this->getManager();
            // Mail::to($appData->email)->send(new NewAdminReplyMail($user->name));
            DB::commit();
            //send push notification to   actor (user - provider)

            if ($message->actor_type == 2) { // ticket from user
                $user = User::where('id', $message->actor_id)->first();
                (new \App\Http\Controllers\NotificationController(['title' => __('messages.New message reply'), 'body' => __('messages.You have new reply on your message')]))
                    ->sendUser($user);
            }

            Flashy::success('تم حفظ الرد بنجاح');
            return redirect()->back();
        } catch (\Exception $ex) {
            dd($ex);
            return view('errors.404');
        }
    }

}

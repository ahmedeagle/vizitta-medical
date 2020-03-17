<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Resources\CPanel\TicketResource;
use App\Http\Resources\CPanel\SingleTicketResource;
use App\Models\Provider;
use App\Models\Replay;
use App\Models\Ticket;
use App\Traits\Dashboard\PublicTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Dashboard\MessageTrait;
use Mail;
use App\Mail\NewAdminReplyMail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProviderMessageController extends Controller
{
    use MessageTrait, PublicTrait;

    public function index()
    {
        $tickets = Ticket::where('actor_type', 1)->paginate(PAGINATION_COUNT);
        $result = new TicketResource($tickets);
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function show(Request $request)
    {
        try {
            $message = $this->getMessageById($request->id);
            if ($message == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $result = new SingleTicketResource($message);
            return response()->json(['status' => true, 'data' => $result]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $message = $this->getMessageById($request->id);
            if ($message == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $message->replays()->delete();
            $message->delete();

            return response()->json(['status' => true, 'msg' => __('main.provider_messages_deleted_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function solvedMessage(Request $request)
    {
        try {
            $message = $this->getMessageById($request->id);
            if ($message == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $message->update(['solved' => 1]);
            return response()->json(['status' => true, 'msg' => __('main.operation_done_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
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
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
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
                    ->sendProviderWeb($provider, '', 'new_replay'); //branch or provider web
            }
            DB::commit();
            return response()->json(['status' => true, 'msg' => __('main.reply_added_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

}

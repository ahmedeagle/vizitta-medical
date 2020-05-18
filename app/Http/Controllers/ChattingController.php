<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\ChatReplay;
 use App\Traits\ChattingTrait;
use App\Traits\GlobalTrait;
use Illuminate\Http\Request;
use Validator;
use Auth;
use Mail;
use JWTAuth;
use DB;

class ChattingController extends Controller
{
    use ChattingTrait, GlobalTrait;

    public function __construct(Request $request)
    {

    }

    public function startChatting(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "consulting_id" => "required|exists:doctor_consulting_reservations,id",
            "actor_type" => "required|in:1,2",  //1 -> user , 2->  doctor
        ]);
        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }

        if ($request->actor_type == 1 or $request->actor_type == '1') {
            $user = $this->auth('user-api');
        } else if ($request->actor_type == 2 or $request->actor_type == '2') {
            //$user = $this->auth('user-api');
        }

        if (!$user) {
            return $this->returnError('D000', trans('messages.User not found'));
        }

        try {
            ##############check if chat is exist and start before###############
            $chatId = 0;
            $checkIfChatExists = Chat::where('consulting_id', $request->consulting_id)
                ->where('chatable_id', $user->id)
                ->where('solved', 0)
                ->first();
            #############if not exist store it###############################
            if (!$checkIfChatExists) {
                $chat = Chat::create([
                    'title' => $request->title ? $request->title : "",
                    'chatable_id' => $user->id,
                    'chatable_type' => ($request->actor_type == 1) ? 'App\Models\User' : 'App\Models\Doctor',
                    'message_no' => $this->getRandomUniqueNumberChatting(8),
                    'consulting_id' => $request->consulting_id,
                ]);
                $chatId = $chat->id;
            } else {
                $chatId = $checkIfChatExists->id;
            }
            ##############then get previous messages as response#############
            $chatData = Chat::find($chatId);
            $messages = ChatReplay::where('chat_id', $chatId)
                ->orderBy('id', 'DESC')
                ->paginate(PAGINATION_COUNT);

            if (count($messages) > 0) {
                $messages->getCollection()->each(function ($message) {
                    if($message -> message_type == 'file')
                        $message ->message = asset($message ->message)  ;
                    return  $message;
                });

            }

            $total_count = $messages->total();
            $messages = json_decode($messages->toJson());
            $messagesJson = new \stdClass();
            $messagesJson->chat_id = $chatId;
            $messagesJson->current_page = $messages->current_page;
            $messagesJson->total_pages = $messages->last_page;
            $messagesJson->total_count = $total_count;
            $messagesJson->per_page = PAGINATION_COUNT;
            $messagesJson->data = $messages->data;
            return $this->returnData('messages', $messagesJson);

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function sendMessage(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "chat_id" => "required|exists:chats,id",
                "message" => "required",
                "message_type" => "required|in:text,file",
                "actor_type" => "required|in:1,2"  // 1 -> user 2->doctor
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }
            DB::beginTransaction();
            $actor_type = $request->actor_type;
            if ($actor_type == 1 or $actor_type == '1')
                $user = $this->auth('user-api');
            else {
                //no more uptill no
            }

            $chat_id = $request->chat_id;
            $message_type = $request->message_type;
            $chat = Chat::find($chat_id);

            if (!$user) {
                return $this->returnError('D000', trans('messages.User not found'));
            }
            if ($chat) {
                if ($chat->chatable_id != $user->id) {
                    return $this->returnError('D000', trans('messages.cannot replay for this converstion'));
                }
            }

            $message = "";
            if ($message_type == 'file') {
                $message = $this->saveFile('chats', $request->message);
            } else {
                $message = $request->message;
            }

            $replay = ChatReplay::create([
                'message' => $message,
                'message_type' => $request->message_type,
                "chat_id" => $chat_id,
                "FromUser" => $actor_type,
                "seenByUser" => '1'
            ]);

            if ($message_type == 'file') {
                $replay -> message = asset($replay -> message);
            }

            DB::commit();
            return $this->returnData('message',$replay,trans('messages.Reply send successfully'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
}

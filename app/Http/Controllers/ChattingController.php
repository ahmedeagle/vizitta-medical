<?php

namespace App\Http\Controllers;

use App\Mail\AcceptReservationMail;
use App\Mail\RejectReservationMail;
use App\Models\Chat;
use App\Models\ChatReplay;
use App\Models\CommentReport;
use App\Models\Doctor;
use App\Models\PromoCode;
use App\Models\Reason;
use App\Models\Ticket;
use App\Models\Replay;
use App\Models\Provider;
use App\Models\ReportingType;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Token;
use App\Models\UserAttachment;
use App\Models\UserRecord;
use App\Traits\ChattingTrait;
use App\Traits\ConsultingTrait;
use App\Traits\DoctorTrait;
use App\Traits\GlobalTrait;
use App\Traits\OdooTrait;
use App\Traits\SMSTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\ProviderTrait;
use App\Mail\NewReplyMessageMail;
use App\Mail\NewUserMessageMail;
use Illuminate\Support\Facades\DB;
use Validator;
use Auth;
use Mail;
use JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use DateTime;

class ChattingController extends Controller
{
    use ChattingTrait;

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

            ########if not exist store it###############################
            $chat = Chat::create([
                'title' => $request->title ? $request->title : "",
                'chatable_id' => $user->id,
                'chatable_type' => ($request->actor_type == 1) ? 'App\Models\User' : 'App\Models\Doctor',
                'message_no' => $this->getRandomUniqueNumberChatting(8),
                'consulting_id' => $user->consulting_id,
            ]);

            ##############then get previous messages as response#############
            $chatData = Chat::find($chat->id);
            $chatMessages = ChatReplay::where('chat_id', $chat->id)->paginate(PAGINATION_COUNT);

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

}

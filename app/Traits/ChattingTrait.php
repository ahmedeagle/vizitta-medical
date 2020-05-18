<?php

namespace App\Traits;

use App\Models\Chat;
use App\Models\ChatReplay;
use App\Models\Doctor;
use App\Models\DoctorConsultingReservation;
use App\Models\Payment;
use App\Models\PromoCode;
use Carbon\Carbon;
use Illuminate\Http\Request;

trait ChattingTrait
{
    protected function getRandomUniqueNumberChatting($length = 8)
    {
        $characters = '0123456789';
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
        $chkCode = Chat::where('message_no', $string)->first();
        if ($chkCode) {
            $this->getRandomUniqueNumberTicket($length);
        }
        return $string;
    }

    public function startChatting($consultReservationId, $userId, $actor_type)
    {
        try {
            ##############check if chat is exist and start before###############
            $chatId = 0;
            $checkIfChatExists = Chat::where('consulting_id', $consultReservationId)
                ->where('chatable_id', $userId)
                ->where('solved', 0)
                ->first();
            #############if not exist store it###############################
            if (!$checkIfChatExists) {
                $chat = Chat::create([
                    'title' => '',
                    'chatable_id' => $userId,
                    'chatable_type' => ($actor_type == 1) ? 'App\Models\User' : 'App\Models\Doctor',
                    'message_no' => $this->getRandomUniqueNumberChatting(8),
                    'consulting_id' => $consultReservationId,
                ]);
                $chatId = $chat->id;
            } else {
                $chatId = $checkIfChatExists->id;
            }
            $consultReservation = DoctorConsultingReservation::find($consultReservationId);
            if($consultReservation){
                $consultReservation -> update(['chatId' => $chatId]);
            }

        } catch (\Exception $ex) {
            //return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

}

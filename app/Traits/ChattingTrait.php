<?php

namespace App\Traits;

use App\Models\Chat;
use App\Models\Doctor;
use App\Models\Payment;
use App\Models\PromoCode;
use Carbon\Carbon;

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

}

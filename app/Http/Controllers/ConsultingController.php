<?php

namespace App\Http\Controllers;

use App\Mail\AcceptReservationMail;
use App\Mail\RejectReservationMail;
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

class ConsultingController extends Controller
{
    use GlobalTrait, ConsultingTrait;

    public function __construct(Request $request)
    {

    }

    public
    function getConsultingDoctors(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "category_id" => "required|exists:specifications,id",
        ]);
        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }

        $validation = $this->validateFields(['specification_id' => $request->specification_id, 'nickname_id' => $request->nickname_id]);

        if (isset($request->specification_id) && $request->specification_id != 0) {
            if ($validation->specification_found == null)
                return $this->returnError('D000', trans('messages.There is no specification with this id'));
        }
        if (isset($request->nickname_id) && $request->nickname_id != 0) {
            if ($validation->nickname_found == null)
                return $this->returnError('D000', trans('messages.There is no nickname with this id'));
        }

        if (isset($request->gender) && $request->gender != 0 && !in_array($request->gender, [1, 2])) {
            return $this->returnError('D000', trans("messages.This is invalid gender"));
        }

        $doctors = $this->getDoctors($request->category_id, $request->nickname_id, $request->gender);

        if (count($doctors) > 0) {
            $doctors->getCollection()->each(function ($doctor) {
                $doctor->branch_name = Doctor::find($doctor->id)->provider->{'name_' . app()->getLocale()};
                $countRate = count($doctor->reservations);
                $doctor->rate_count = $countRate;
                $doctor->makeHidden(['rate_count', 'hide', 'available_time','reservations']);
                return $doctor;
            });

            $total_count = $doctors->total();
            $doctors = json_decode($doctors->toJson());
            $doctorsJson = new \stdClass();
            $doctorsJson->current_page = $doctors->current_page;
            $doctorsJson->total_pages = $doctors->last_page;
            $doctorsJson->per_page = PAGINATION_COUNT;
            $doctorsJson->total_count = $total_count;
            $doctorsJson->data = $doctors->data;
            return $this->returnData('doctors', $doctorsJson);
        }
        return $this->returnError('E001', trans('messages.there is no data found'));
    }


}
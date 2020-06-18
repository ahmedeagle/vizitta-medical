<?php

namespace App\Http\Controllers;

use App\Mail\AcceptReservationMail;
use App\Mail\RejectReservationMail;
use App\Models\CommentReport;
use App\Models\Doctor;
use App\Models\DoctorConsultingReservation;
use App\Models\Mix;
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

        $doctors = $this->getDoctors($request->category_id, $request->nickname_id, $request->gender,$request -> specification_id);

       // if (count($doctors) > 0) {
            $doctors->getCollection()->each(function ($doctor) {
                $doctor->branch_name = Doctor::find($doctor->id)->provider->{'name_' . app()->getLocale()};
                $countRate = count($doctor->reservations);
                $doctor->rate_count = $countRate;
                $doctor->makeHidden(['rate_count', 'hide', 'available_time', 'reservations']);
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

         //   return $this->returnData('doctors', $doctorsJson);

        //return $this->returnError('E001', trans('messages.there is no data found'));
    }

    public function getCurrentConsultingReserves(Request $request)
    {
        try {
            $user = $this->auth('user-api');
            $consultings = $this->getCurrentReservations($user->id);

            if (isset($consultings) && $consultings->count() > 0) {
                foreach ($consultings as $key => $consulting) {
                    $consulting_start_date = date('Y-m-d H:i:s', strtotime($consulting->day_date . ' ' . $consulting->from_time));
                    $consulting_end_date = date('Y-m-d H:i:s', strtotime($consulting->day_date . ' ' . $consulting->to_time));
                    $consulting->consulting_start_date = $consulting_start_date;
                    $consulting->consulting_end_date = $consulting_end_date;
                    //return $consulting_start_date .' > = '.date('Y-m-d H:i:s');
                    if (date('Y-m-d H:i:s') >= $consulting_start_date && ($this->getDiffBetweenTwoDate(date('Y-m-d H:i:s'), $consulting_start_date) <= $consulting->hours_duration)) {
                        $consulting->allow_chat = 1;
                    } else {
                        $consulting->allow_chat = 0;
                    }
                    $consulting->makeHidden(['day_date', 'from_time', 'to_time', 'rejected_reason_type', 'reservation_total', 'for_me', 'is_reported', 'branch_name', 'branch_no', 'mainprovider', 'admin_value_from_reservation_price_Tax']);
                    $consulting->doctor->makeHidden(['times']);
                }
            }

            if (count($consultings->toArray()) > 0) {
                $total_count = $consultings->total();
                $consultings = json_decode($consultings->toJson());
                $consultingsJson = new \stdClass();
                $consultingsJson->current_page = $consultings->current_page;
                $consultingsJson->total_pages = $consultings->last_page;
                $consultingsJson->total_count = $total_count;
                $consultingsJson->per_page = PAGINATION_COUNT;
                $consultingsJson->data = $consultings->data;
                return $this->returnData('reservations', $consultingsJson);
            }
            return $this->returnError('E001', trans('messages.No medical consulting founded'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getFinishedConsultingReserves(Request $request)
    {
        try {
            $user = $this->auth('user-api');
            $consultings = $this->getFinishedReservations($user->id);
            if (isset($consultings) && $consultings->count() > 0) {
                foreach ($consultings as $key => $consulting) {
                    $consulting->allow_chat = 0;
                    $consulting->makeHidden(['day_date', 'from_time', 'to_time', 'rejected_reason_type', 'reservation_total', 'for_me', 'is_reported', 'branch_name', 'branch_no', 'mainprovider', 'admin_value_from_reservation_price_Tax']);
                    $consulting->doctor->makeHidden(['times']);
                }
            }

            if (count($consultings->toArray()) > 0) {
                $total_count = $consultings->total();
                $consultings = json_decode($consultings->toJson());
                $consultingsJson = new \stdClass();
                $consultingsJson->current_page = $consultings->current_page;
                $consultingsJson->total_pages = $consultings->last_page;
                $consultingsJson->total_count = $total_count;
                $consultingsJson->per_page = PAGINATION_COUNT;
                $consultingsJson->data = $consultings->data;
                return $this->returnData('reservations', $consultingsJson);
            }
            return $this->returnError('E001', trans('messages.No medical consulting founded'));

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getConsultingIfo(Request $request)
    {
        $setting = Mix::select('consulting_text', 'consulting_photo')->first();
        if (!$setting)
            return $this->returnError('E001', trans('messages.No data founded'));
        return $this->returnData('consulting_info', $setting);

    }

    public function getConsultingReserves(Request $request)
    {
        try {
            $type = "all";
            if ($request->has('type')) {
                if ($request->type == 0) {//pending and approved reservation
                    $type = 'current';
                } elseif ($request->type == 1) {
                    $type = 'finished';       // cancelled -> 2 done ->3
                }
            }
            $user = $this->auth('user-api');
             $consultings = $this->getAllReservations($user->id, $type);
            if (isset($consultings) && $consultings->count() > 0) {
                foreach ($consultings as $key => $consulting) {
                    $consulting -> nickname = $consulting -> doctor -> nickname;
                    $consulting_start_date = date('Y-m-d H:i:s', strtotime($consulting->day_date . ' ' . $consulting->from_time));
                    $consulting_end_date = date('Y-m-d H:i:s', strtotime($consulting->day_date . ' ' . $consulting->to_time));
                    $currentDate = date('Y-m-d H:i:s');
                    $consulting->consulting_start_date = $consulting_start_date;
                    $consulting->consulting_end_date = $consulting_end_date;
                    $consulting->currentDate = $currentDate;
                    if (($currentDate >= $consulting_start_date) && ($currentDate <= $consulting_end_date) && $consulting->approved == 1) {
                        $consulting->active_now = 1;
                    } else {
                        $consulting->active_now = 0;
                    }
                    if (date('Y-m-d H:i:s') >= $consulting_end_date) {
                        $consulting->approved = '3';
                        DoctorConsultingReservation::where('id', $consulting->id)->update(['approved' => '3']);
                    }

                    $consulting->makeHidden(['day_date', 'from_time', 'to_time', 'rejected_reason_type', 'reservation_total', 'for_me', 'is_reported', 'branch_name', 'branch_no', 'mainprovider', 'admin_value_from_reservation_price_Tax']);
                    $consulting->doctor->makeHidden(['times']);
                }
            }

            if (count($consultings->toArray()) > 0) {
                $total_count = $consultings->total();
                $consultings = json_decode($consultings->toJson());
                $consultingsJson = new \stdClass();
                $consultingsJson->current_page = $consultings->current_page;
                $consultingsJson->total_pages = $consultings->last_page;
                $consultingsJson->total_count = $total_count;
                $consultingsJson->per_page = PAGINATION_COUNT;
                $consultingsJson->data = $consultings->data;
                return $this->returnData('reservations', $consultingsJson);
            }
            return $this->returnError('E001', trans('messages.No medical consulting founded'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


}

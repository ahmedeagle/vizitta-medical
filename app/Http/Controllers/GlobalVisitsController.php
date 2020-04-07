<?php

namespace App\Http\Controllers;

use App\Models\ServiceReservation;
use App\Models\Service;
use App\Traits\GlobalTrait;
use App\Traits\SearchTrait;
use http\Env\Response;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PHPUnit\Framework\Constraint\Count;
use Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class GlobalVisitsController extends Controller
{
    use GlobalTrait, SearchTrait;

    public function getClinicServiceAvailableTimes(Request $request)
    {
        try {
            $requestData = $request->all();
            $dayName = Str::lower(date('D', strtotime($requestData['reserve_day'])));
            $service = Service::find($requestData['service_id']);
            $serviceTimes = [];

            if ($requestData['service_type'] == 'clinic') {
                if ($service) {
                    $serviceTimes = $service->times()->whereNotNull('reservation_period')->where('day_code', $dayName)->get();
                }
            } else {
                if ($service) {
                    $times = $service->times()->whereNull('reservation_period')->where('day_code', $dayName)->get();
                    foreach ($times as $key => $value) {
                        $splitTimes = $this->splitTimes($value->from_time, $value->to_time, $requestData['reserve_duration']);
                        foreach ($splitTimes as $k => $v) {
                            $s = [];
                            $s['id'] = $value->id;
                            $s['day_name'] = $value->day_name;
                            $s['day_code'] = $value->day_code;
                            $s['from_time'] = $v['from'];
                            $s['to_time'] = $v['to'];
                            $s['branch_id'] = $value->branch_id;

                            array_push($serviceTimes, $s);
                        }

                    }
                }
            }

            if ($serviceTimes)
                return $this->returnData('times', $serviceTimes);

            return $this->returnError('E001', trans('main.there_is_no_times_now'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function reserveHomeClinicService(Request $request)
    {
        try {

            $requestData = $request->all();
            $rules = [
                "service_id" => "required|numeric",
                "day_date" => "required|date",
                "from_time" => "required",
                "to_time" => "required",
                "price" => "required",
            ];
            $validator = Validator::make($requestData, $rules);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $service = Service::find($requestData['service_id']);
//            dd($user = $this->auth('user-api'));
            /*$user = $this->auth('user-api');
            if ($user == null)
                return $this->returnError('E001', trans('messages.There is no user with this id'));*/

            $reservationCode = $this->getRandomString(8);
            $reservation = ServiceReservation::create([
                "reservation_no" => $reservationCode,
//                "user_id" => $user->id,
                "service_id" => $service->id,
                "day_date" => date('Y-m-d', strtotime($requestData['day_date'])),
                "from_time" => date('H:i:s', strtotime($requestData['from_time'])),
                "to_time" => date('H:i:s', strtotime($requestData['to_time'])),
                "paid" => 0,
                "provider_id" => $service->provider_id,
                'price' => (!empty($request->price) ? $requestData['price'] : $service->price),
            ]);

            return $this->returnData('reservation', $reservation);

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function splitTimes($StartTime, $EndTime, $Duration = "30")
    {
        $returnArray = [];// Define output
        $StartTime = strtotime($StartTime); //Get Timestamp
        $EndTime = strtotime($EndTime); //Get Timestamp

        $addMinutes = $Duration * 60;

        for ($i = 0; $StartTime <= $EndTime; $i++) //Run loop
        {
            $from = date("G:i", $StartTime);
            $StartTime += $addMinutes; //End time check
            $to = date("G:i", $StartTime);
            if ($EndTime >= $StartTime) {
                $returnArray[$i]['from'] = $from;
                $returnArray[$i]['to'] = $to;
            }
        }
        return $returnArray;
    }

    protected function getRandomString($length)
    {
        $characters = '0123456789';
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
        $chkCode = ServiceReservation::where('reservation_no', $string)->first();
        if ($chkCode) {
            $this->getRandomString(8);
        }
        return $string;
    }

}

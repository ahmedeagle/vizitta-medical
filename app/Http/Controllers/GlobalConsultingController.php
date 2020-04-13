<?php

namespace App\Http\Controllers;

use App\Http\Resources\SingleDoctorResource;
use App\Models\Doctor;
use App\Models\Specification;
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

class GlobalConsultingController extends Controller
{
    use GlobalTrait, SearchTrait;

    public function getConsultingCategories(Request $request)
    {
        try {

            $result = Specification::whereHas('doctors', function ($q) {
                $q->where('doctor_type', 'consultative');
            })->get(['id', DB::raw('name_' . $this->getCurrentLang() . ' as name')]);
            return $this->returnData('specifications', $result);

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getConsultingDoctorDetails(Request $request)
    {
        try {
            $requestData = $request->only(['doctor_id']);
            $doctor = Doctor::where('doctor_type', 'consultative')->find($requestData['doctor_id']);

            $result = new SingleDoctorResource($doctor);
            return $this->returnData('doctor', $result);

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getConsultingDoctorTimes(Request $request)
    {
        try {
            $requestData = $request->only(['doctor_id', 'reserve_duration', 'day_date']);
            $doctor = Doctor::where('doctor_type', 'consultative')->find($requestData['doctor_id']);
            $dayName = Str::lower(date('D', strtotime($requestData['day_date'])));

            if ($doctor) {

                $doctorTimes = [];

                if (count($doctor->consultativeTimes) > 0)
                    $times = $doctor->consultativeTimes()->where('day_code', $dayName)->get();
                else
                    $times = $doctor->times()->where('day_code', $dayName)->get();

                if ($times) {
                    foreach ($times as $key => $value) {
                        $splitTimes = $this->splitTimes($value->from_time, $value->to_time, $requestData['reserve_duration']);
                        foreach ($splitTimes as $k => $v) {
                            $s = [];
                            $s['id'] = $value->id;
                            $s['day_name'] = $value->day_name;
                            $s['day_code'] = $value->day_code;
                            $s['from_time'] = $v['from'];
                            $s['to_time'] = $v['to'];
                            $s['reservation_period'] = $value['reservation_period'];

                            array_push($doctorTimes, $s);
                        }

                    }
                }

            }

            return $this->returnData('times', $doctorTimes);

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    ##########################################################################

    public function getCurrentLang()
    {
        return app()->getLocale();
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


}

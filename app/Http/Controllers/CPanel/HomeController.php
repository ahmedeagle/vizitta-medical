<?php

namespace App\Http\Controllers\CPanel;

use App\Models\Doctor;
use App\Models\OfferBranchTime;
use App\Models\Reservation;
use App\Traits\Dashboard\PublicTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    use PublicTrait;

    public function index()
    {
        /*if (Auth::guard('manager-api')->user()->can('random_drawing')) {
            return redirect()->route('admin.lotteries.drawing');
        }*/

        $data['activeProvidersCount'] = $this->getActiveProviders(true);
        $data['activeDoctorsCount'] = $this->getActiveDoctors(true);
        $data['activeUsersCount'] = $this->getActiveUsers(true);
        $data['finishedPaidReservationsCount'] = $this->getFinishedPaidReservations(true);
        $data['futureReservationsCount'] = $this->getFutureReservations(true);
        $data['paidReservationsCount'] = $this->getPaidReservations(true);
        $data['mostBookingDay'] = Reservation::groupBy('day_date')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get(['day_date', DB::raw('count(day_date) as count')]);

        $data['mostBookingHour'] = Reservation::groupBy('from_time')
            ->orderBy('count', 'desc')
            ->limit(5)->get(['from_time', 'to_time', DB::raw('count(from_time) as count')]);

        $data['mostBookingProviders'] = Reservation::groupBy('provider_id')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get(['provider_id', DB::raw('count(provider_id) as count')]);

        $data['mostBookingDoctor'] = Reservation::groupBy('doctor_id')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get(['doctor_id', DB::raw('count(doctor_id) as count')]);
        // $reservation_notReplayForMore15Mins= Reservation::notReplay15Min() -> count();
        $allowTime = 15;  // 15 minutes
        $data['reservation_notReplayForMore15Mins'] = Reservation::where('approved', 0)->whereRaw('ABS(TIMESTAMPDIFF(MINUTE,created_at,CURRENT_TIMESTAMP)) >= ?', $allowTime)->count();

        $todayReservation = Reservation::today()->count();
        $tommorrow = Reservation::tommorow()->count();
        $data['todayAndTomorrowReservationCount'] = $todayReservation + $tommorrow;

        $specifications = Doctor::whereHas('reservations')
            ->groupBy('specification_id')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get(['id as doctor_id', 'specification_id', DB::raw('count(specification_id) as count')]);

        foreach ($specifications as $specification) {
            $specification->makeVisible(['specification_id']);
            $specification->makeHidden(['hide', 'doctor_id']);
        }
        $data['mostBookingSpecifications'] = $specifications;

        return response()->json(['status' => true, 'data' => $data]);
    }

    public function search(Request $request)
    {
        $queryStr = $request->queryStr;
        $type = $request->type_id;
        if ($type) {
            if ($type != 'provider' && $type != 'branch' && $type != 'doctor' && $type != 'users') {
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
            }
        } else {
            return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
        }

        $result['page'] = $type;
        $result['queryStr'] = $queryStr;
        return response()->json(['status' => true, 'data' => $result]);

//        $url = "mc33/{$type}/?queryStr=" . $queryStr;
//        return redirect($url);
    }

    public function branchTimes(Request $request)
    {
        $input = $request->only('branchTimes');
        $branches = $input['branchTimes'];
        foreach ($branches as $branchId => $branch) {
            foreach ($branch['days'] as $dayCode => $time) {
//                $this->splitTimes(1, $branchId, $dayCode, $time['from'], $time['to'], $branch['duration']);
//                $returnTimes = $this->splitTimes($time['from'], $time['to'], $branch['duration']);
//                $this->storeOfferBranchTimes(1, $branchId, $dayCode, $returnTimes);
            }
        }

        return response()->json(['status' => true]);

    }

    public function splitTimes($offerId, $branchId, $dayCode, $StartTime, $EndTime, $Duration = "60")
    {
        $returnArray = [];// Define output
        $StartTime = strtotime($StartTime); //Get Timestamp
        $EndTime = strtotime($EndTime); //Get Timestamp

        $addMinutes = $Duration * 60;

        /*for ($i = 0; $StartTime <= $EndTime; $i++) //Run loop
        {
            $from = date("G:i", $StartTime);
            $StartTime += $addMinutes; //End time check
            $to = date("G:i", $StartTime);
            if ($EndTime >= $StartTime) {
                $returnArray[$i]['from'] = $from;
                $returnArray[$i]['to'] = $to;
            }
        }*/
//        return $returnArray;

        for ($i = 0; $StartTime <= $EndTime; $i++) //Run loop
        {
            $from = date("G:i", $StartTime);
            $StartTime += $addMinutes; //End time check
            $to = date("G:i", $StartTime);
            if ($EndTime >= $StartTime) {

                OfferBranchTime::create([
                    'offer_id' => $offerId,
                    'branch_id' => $branchId,
                    'day_code' => $dayCode,
                    'time_from' => $from,
                    'time_to' => $to,
                ]);
            }
        }
    }

    /*public function storeOfferBranchTimes($offerId, $branchId, $dayCode, $times)
    {
        foreach ($times as $key => $value) {
            OfferBranchTime::create([
                'offer_id' => $offerId,
                'branch_id' => $branchId,
                'day_code' => $dayCode,
                'time_from' => $value['from'],
                'time_to' => $value['to'],
            ]);
        }
    }*/
}

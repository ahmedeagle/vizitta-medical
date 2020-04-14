<?php

namespace App\Traits;

use App\Models\Doctor;
use App\Models\DoctorConsultingReservation;
use App\Models\Message;
use App\Models\Ticket;
use App\Models\Provider;
use App\Models\ProviderType;
use App\Models\Reservation;
use Carbon\Carbon;
use DateTime;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait ConsultingTrait
{
    public function getDoctors($specification_id, $nickname_id, $gender)
    {

        $doctor = Doctor::query();
        $doctor = $doctor->where('doctor_type', 'consultative')
            ->where('specification_id', $specification_id)
            ->with(['specification' => function ($q1) {
                $q1->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'));
            }]);

        if ($nickname_id != null && $nickname_id != 0)
            $doctor = $doctor->where('nickname_id', $nickname_id);

        if ($gender != null && $gender != 0 && in_array($gender, [1, 2]))
            $doctor = $doctor->where('gender', $gender);

        $doctor = $doctor->select('id', 'specification_id', 'photo', 'rate', 'price', 'reservation_period',
            DB::raw('name_' . $this->getCurrentLang() . ' as name')
        );
        return $doctor->where('doctors.status', 1)->paginate(PAGINATION_COUNT);
    }


    function getDiffBetweenTwoDate($startDate, $endDate, $formate = 'i')
    {
        $fdate = $startDate;
        $tdate = $endDate;
        $datetime1 = new DateTime($fdate);
        $datetime2 = new DateTime($tdate);
        $interval = $datetime1->diff($datetime2);
        $days = $interval->format('%i');
        return $days;
    }
    public function getCurrentReservations($id)
    {
        return DoctorConsultingReservation::current()
            ->with([
                'doctor' => function ($q) {
                    $q->select('id', 'photo', 'specification_id', DB::raw('name_' . app()->getLocale() . ' as name'))->with(['specification' => function ($qq) {
                        $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
                }, 'paymentMethod' => function ($qu) {
                    $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                }])
            ->where('user_id', $id)
            //->where('day_date', '>=', Carbon::now()
            //  ->format('Y-m-d'))
            ->orderBy('day_date')
            ->orderBy('order')
            ->select('id','doctor_id','payment_method_id','total_price','hours_duration','day_date','from_time','to_time')
            ->paginate(PAGINATION_COUNT);
    }

    public function getFinishedReservations($id)
    {
        return DoctorConsultingReservation::finished()->with(['doctor' => function ($q) {
            $q->select('id', 'specification_id', DB::raw('name_' . app()->getLocale() . ' as name'), DB::raw('abbreviation_' . app()->getLocale() . ' as abbreviation'), DB::raw('information_' . app()->getLocale() . ' as information'))->with(['specification' => function ($qq) {
                $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }]);
        }, 'provider' => function ($que) {
            $que->select('id', 'provider_id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }, 'paymentMethod' => function ($qu) {
            $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
        }])
            ->where('user_id', $id)
            ->orderBy('day_date')
            ->orderBy('order')
            ->paginate(PAGINATION_COUNT);
    }
}

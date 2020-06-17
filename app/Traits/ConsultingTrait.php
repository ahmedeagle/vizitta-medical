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
    public function getDoctors($specification_id, $nickname_id, $gender,$_specification_id = null)
    {

        if($_specification_id != null && $_specification_id !="" && $_specification_id != 0)
        {
            $specification_id  = $_specification_id;
        }
        $doctor = Doctor::with(['nickname' => function($q){
            $q -> select('id','name_'.app()->getLocale().' as name');
        }]);
        $doctor = $doctor->where(function ($q) {
            $q->where('doctor_type', 'consultative')
                ->orwhere('is_consult', 1);
        })->where('specification_id', $specification_id)
            ->with(['specification' => function ($q1) {
                $q1->select('id', \Illuminate\Support\Facades\DB::raw('name_' . app()->getLocale() . ' as name'));
            }]);

        if ($nickname_id != null && $nickname_id != 0)
            $doctor = $doctor->where('nickname_id', $nickname_id);

        if ($gender != null && $gender != 0 && in_array($gender, [1, 2]))
            $doctor = $doctor->where('gender', $gender);

        $doctor = $doctor->select('id', 'nickname_id','specification_id', 'photo', 'rate', 'price','price_consulting', 'reservation_period',
            DB::raw('name_' . $this->getCurrentLang() . ' as name')
        );
        return $doctor->where('doctors.status', 1)->paginate(PAGINATION_COUNT);
    }


    function getDiffBetweenTwoDate($ConsultingDate)
    {
        $end = Carbon::parse($ConsultingDate, 'Asia/Riyadh');
        $now = Carbon::now('Asia/Riyadh');
        return $length = $now->diffInMinutes($end);
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
            ->select('id', 'doctor_id', 'payment_method_id', 'total_price', 'hours_duration', 'day_date', 'from_time', 'to_time')
            ->paginate(PAGINATION_COUNT);
    }

    public function getFinishedReservations($id)
    {
        return DoctorConsultingReservation::finished()
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
            ->select('id', 'doctor_id', 'payment_method_id', 'total_price', 'hours_duration', 'day_date', 'from_time', 'to_time', 'doctor_rate', 'rate_comment', 'rate_date')
            ->paginate(PAGINATION_COUNT);
    }


    public function getAllReservations($id, $type = 'all')
    {
        if ($type == 'current') {  //pending and approved
            $conditions = ['0', '1'];
        } elseif ($type ==  'finished') {  //cancelled and  done
            $conditions = ['2', '3'];
        } else { //all reservation
            $conditions = ['0', '1', '2', '3'];
        }
        return DoctorConsultingReservation::with([
            'doctor' => function ($q) {
                $q->select('id', 'photo', 'rate', 'reservation_period','nickname_id' ,'specification_id', DB::raw('name_' . app()->getLocale() . ' as name'), 'price')
                    ->with(['specification' => function ($qq) {
                    $qq->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                },'nickname' => function ($qu) {
                        $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                    }]);
            }, 'paymentMethod' => function ($qu) {
                $qu->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
            }])
            ->where('user_id', $id)
            //->where('day_date', '>=', Carbon::now()
            //  ->format('Y-m-d'))
            ->whereIn('approved', $conditions)
            ->orderBy('day_date')
            ->orderBy('order')
            ->select('id', 'approved', 'doctor_id', 'payment_method_id', 'total_price', 'hours_duration', 'day_date', 'from_time', 'to_time', 'doctor_rate', 'rate_comment', 'rate_date', 'chatId')
            ->paginate(PAGINATION_COUNT);
    }
}

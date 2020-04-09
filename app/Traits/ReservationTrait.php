<?php

namespace App\Traits;

use App\Models\Reservation;
use App\Models\ServiceReservation;

trait ReservationTrait
{
    public function getReservationByID($id, $user_id = null){
        $reservation = Reservation::query();
        if($user_id != null)
            $reservation = $reservation->where('user_id', $user_id);

        return $reservation->where('id', $id)->first();
    }

    public function getReservationWithData($id, $user_id = null){
        $reservation = Reservation::query();
        if($user_id != null)
            $reservation->where('user_id', $user_id);
        $reservation->with('doctor');
        $reservation->with('doctor.reservations');
        $reservation->with('provider');
        $reservation->with('provider.reservations');
        return $reservation->find( $id);
    }


    public function getServiceReservationWithData($id, $user_id = null){
        $reservation = ServiceReservation::query();
        if($user_id != null)
            $reservation->where('user_id', $user_id);
        $reservation->with('service');
       // $reservation->with('service.reservations');
        $reservation->with('provider');
       // $reservation->with('provider.reservations');
        return $reservation->find( $id);
    }


}

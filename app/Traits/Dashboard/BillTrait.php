<?php

namespace App\Traits\Dashboard;

use App\Models\Bill;
use App\Models\Point;
use App\Models\Provider;
use App\Models\Reservation;
use Freshbitsweb\Laratables\Laratables;

trait BillTrait
{
    public function getBillById($id)
    {
        return Bill::find($id);
    }

    public function getAll()
    {
        return Laratables::recordsOf(Bill::class, function ($query) {
            return $query->has('reservation')->select('*') -> orderBy('created_at','DESC');
        });


    }


    public function checkUserPoints($userId, $reservationId)
    {
        return Point::where('user_id', $userId)->where('reservation_id', $reservationId)->first();
    }

    public function createBill($request)
    {
        $Bill = Bill::create($request->all());
        return $Bill;
    }

    public function updateBill($Bill, $request)
    {
        $Bill = $Bill->update($request->all());
        return $Bill;
    }

}

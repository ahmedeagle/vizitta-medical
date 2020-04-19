<?php

namespace App\Traits\Dashboard;

use App\Models\Doctor;
use Freshbitsweb\Laratables\Laratables;

trait DoctorTrait
{
    public function getDoctorById($id)
    {
        return Doctor::find($id);
    }

    public function getAll($queryStr)
    {
        return Laratables::recordsOf(Doctor::class, function ($query) use($queryStr) {
            return $query -> where(function($q) use($queryStr){
                return $q ->where('name_en', 'LIKE', '%' . trim($queryStr) . '%')->orWhere('name_ar', 'LIKE', '%' . trim($queryStr) . '%');
            });
        });
     }

    public function createDoctor($request)
    {
        $doctor = Doctor::create($request->all());
        return $doctor;
    }

    public function updateDoctor($doctor, $request)
    {
        $doctor = $doctor->update($request->all());
        return $doctor;
    }

    public function changerDoctorStatus($doctor, $status)
    {
        $doctor = $doctor->update([
            'status' => $status
        ]);
        return $doctor;
    }

    public static function getDoctorNameById($doctor_id)
    {
        $doctor = Doctor::find($doctor_id);
        if (!$doctor)
            return '';

        return $doctor-> {'name_'.app()->getLocale()};
    }
}

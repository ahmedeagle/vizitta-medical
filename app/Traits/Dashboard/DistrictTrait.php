<?php

namespace App\Traits\Dashboard;
use App\Models\District;
use Freshbitsweb\Laratables\Laratables;

trait DistrictTrait
{
    public function getDistrictById($id){
        return District::find($id);
    }

    public function getAll(){
        return Laratables::recordsOf(District::class);
    }

    public function createDistrict($request){
        $district = District::create($request->all());
        return $district;
    }

    public function updateDistrict($district, $request){
        $district = $district->update($request->all());
        return $district;
    }

}
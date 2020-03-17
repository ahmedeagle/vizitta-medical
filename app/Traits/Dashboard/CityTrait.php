<?php

namespace App\Traits\Dashboard;
use App\Models\City;
use Freshbitsweb\Laratables\Laratables;

trait CityTrait
{
    public function getCityById($id){
        return City::find($id);
    }

    public function getAll(){
        return Laratables::recordsOf(City::class);
    }

    public function createCity($request){
        $city = City::create($request->all());
        return $city;
    }

    public function updateCity($city, $request){
        $city = $city->update($request->all());
        return $city;
    }

}
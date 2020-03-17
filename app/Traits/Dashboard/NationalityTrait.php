<?php

namespace App\Traits\Dashboard;
use App\Models\Nationality;
use Freshbitsweb\Laratables\Laratables;

trait NationalityTrait
{
    public function getNationalityById($id){
        return Nationality::find($id);
    }

    public function getAll(){
        return Laratables::recordsOf(Nationality::class);
    }

    public function createNationality($request){
        $nationality = Nationality::create($request->all());
        return $nationality;
    }

    public function updateNationality($nationality, $request){
        $nationality = $nationality->update($request->all());
        return $nationality;
    }
    
}
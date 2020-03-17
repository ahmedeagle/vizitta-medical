<?php

namespace App\Traits\Dashboard;
use Freshbitsweb\Laratables\Laratables;
use App\Models\ProviderType;

trait ProviderTypesTrait
{

    public function getProviderTypeById($id){
        return ProviderType::find($id);
    }

    public function getAllProviderTypes(){
        return Laratables::recordsOf(ProviderType::class);
    }

    public function createProviderType($request){
        $company = ProviderType::create($request->all());
        return $company;
    }

    public function updateProviderType($ProviderType, $request){
        $ProviderType = $ProviderType->update($request->all());
        return $ProviderType;
    }

    public static function getProviderTypeNameById($ProviderType_id){
        $ProviderType = ProviderType::find($ProviderType_id);
        if(!$ProviderType)
            return '--';
        return $ProviderType -> name_ar;

    }

}

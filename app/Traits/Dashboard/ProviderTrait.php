<?php

namespace App\Traits\Dashboard;

use App\Models\Provider;
use Freshbitsweb\Laratables\Laratables;
use DB;

trait ProviderTrait
{
    // Providers
    public function getProviderById($id)
    {
        return Provider::find($id);
    }

    public function getAllProviders($queryStr)
    {
        return Laratables::recordsOf(Provider::class, function ($query) use($queryStr) {
            return $query->where('provider_id',null) -> where(function($q) use($queryStr){
                return $q ->where('name_en', 'LIKE', '%' . trim($queryStr) . '%')->orWhere('name_ar', 'LIKE', '%' . trim($queryStr) . '%');
            });
        });
    }

    public function updateProvider($provider, $request)
    {
        $provider = $provider->update($request->all());
        return $provider;
    }

    public function changerProviderStatus($provider, $status)
    {
        $providerUp = $provider->update([
            'status' => $status
        ]);
        // if  hide main provider its branches also hidden
        foreach ($provider->providers as $branch) {
            $branch->update([
                'status' => $status
            ]);
        }
        return $providerUp;
    }

    // Branches
    public function getAllBranches($queryStr)
    {
        return Laratables::recordsOf(Provider::class, function ($query) use($queryStr) {
            return $query->whereNotNull('provider_id') -> where(function($q) use($queryStr){
                        return $q ->where('name_en', 'LIKE', '%' . trim($queryStr) . '%')->orWhere('name_ar', 'LIKE', '%' . trim($queryStr) . '%');
            });
        });
    }


    public function checkIfMobileExistsForOtherBranches($mobile)
    {
        $exists = Provider::whereNotNull('provider_id')->where('mobile', $mobile)->first();
        if ($exists) {
            return true;
        }
        return false;
    }

    public function checkIfMobileExistsForOtherProviders($mobile)
    {
        $exists = Provider::where('provider_id', null)->where('mobile', $mobile)->first();
        if ($exists) {
            return true;
        }
        return false;
    }

    public static function getProviderNameById($provider_id)
    {
        $provider = Provider::find($provider_id);
        if (!$provider)
            return '--';
        $mainProvider = $provider -> provider -> name_ar;
        return $provider->name_ar .' - '. $mainProvider;
    }

}

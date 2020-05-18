<?php

namespace App\Http\Controllers\CPanel;

use App\Models\City;
use App\Traits\CPanel\GeneralTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GeneralController extends Controller
{
    use GeneralTrait;

    public function getAllCitiesList(Request $request)
    {
        $result = $this->getCities();
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function getAllDistrictsListByCityId(Request $request)
    {
        $city = City::find($request->id);
        if ($city) {
            $result = $this->getDistrictsByCityId($city->id);
            return response()->json(['status' => true, 'data' => $result]);
        } else
            return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
    }

    public function getAllProviderTypesList(Request $request)
    {
        $result = $this->getProviderTypes();
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function getAllDoctorsNicknamesList(Request $request)
    {
        $result = $this->apiGetAllNicknames();
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function getAllInsuranceCompaniesList(Request $request)
    {
        $result = $this->apiGetAllInsuranceCompaniesWithSelected();
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function getAllProvidersList(Request $request)
    {
        $result = $this->getMainActiveProviders();
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function getAllBranchesList(Request $request)
    {
        $result = $this->getMainActiveBranches();
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function getAllProviderBranchesList(Request $request)
    {
        $result = $this->getMainActiveProviderBranches($request->id);
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function getAllSpecificationsList(Request $request)
    {
        $result = $this->apiGetAllSpecifications();
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function getTransactionDetails(Request $request){

        dd($request->all());
        try {
          /*  $rules = [
                trans
            ];*/

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }

        }

}

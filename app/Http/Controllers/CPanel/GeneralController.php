<?php

namespace App\Http\Controllers\CPanel;

use App\Models\City;
use App\Models\Service;
use App\Traits\CPanel\GeneralTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use DB;
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

    public function getTransactionDetails(Request $request)
    {
        try {
            $rules = [
                "transaction_id" => 'required'
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $url = "https://test.oppwa.com/v1/query/";
            $url .= $request->transaction_id;
            $url .= "?entityId=" . env('PAYTABS_ENTITYID', '8ac7a4ca6d0680f7016d14c5bbb716d8');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization:Bearer ' . env('PAYTABS_AUTHORIZATION', 'OGFjN2E0Y2E2ZDA2ODBmNzAxNmQxNGM1NzMwYzE2ZDR8QVpZRXI1ZzZjZQ')));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, env('PAYTABS_SSL', false));// this should be set to true in production
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $responseData = curl_exec($ch);
            if (curl_errno($ch)) {
                return $this->returnError('D001', 'حدث خطا ما الرجاء المحاولة مجددا');
            }
            curl_close($ch);
            return $this->returnData('data', json_decode($responseData, true), '', 'S001');
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }


    public function changeStatusByType(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "type" => "required|in:services",
                "status" => "required|in:0,1",
                "id" => "required"

            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $type = $request->type;
            $status = $request->status;
            $id = $request->id;

            if ($type == 'services') {
                $service = Service::find($id);
                if (!$service)
                    return $this->returnError('D000', trans("messages.no service with this id"));

                $service->update(['status' => $status]);
            }

            return $this->returnSuccessMessage(trans('messages.status changed successfully'));

        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function changeStatus(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                "type" => "required|in:provider_types,specifications,doctor_nicknames,offers_categories,reasons",
                "status" => "required|in:0,1",
                "id" => "required"
            ]);

            if ($validator->fails()) {
                $code = $this->returnCodeAccordingToInput($validator);
                return $this->returnValidationError($code, $validator);
            }

            $table = $request->type;
            $status = $request->status;

             $_table = DB::table($table)->find($request->id);


            if (!$_table)
                return $this->returnError('E001', __('Data not Found'));

            DB::table($table)->where('id',$request->id)-> update(['status'=>$status]);

            return $this->returnSuccessMessage(trans('messages.status changed successfully'));

        } catch (\Exception $ex) {
            return $ex;
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
}

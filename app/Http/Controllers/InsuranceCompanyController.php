<?php

namespace App\Http\Controllers;

use App\Traits\GlobalTrait;
use Illuminate\Http\Request;

class InsuranceCompanyController extends Controller
{
    use GlobalTrait;

    public function index(Request $request){
        try {
            if(isset($request->doctor_id) && $request->doctor_id != 0){
                $doctor = $this->checkDoctor($request->doctor_id);
                if($doctor == null)
                    return $this->returnError('E001', trans('messages.There is no doctor with this id'));

                $insuranceCompanies = $this->getAllInsuranceCompanies($request->doctor_id);
            }
            else if(isset($request->branch_id) && $request->branch_id != 0){
                $provider = $this->checkProvider($request->branch_id);
                if($provider == null)
                    return $this->returnError('E001', trans('messages.There is no provider with this id'));

                $insuranceCompanies = $this->getAllInsuranceCompanies(null, $request->branch_id);
            } else {
                $insuranceCompanies = $this->getAllInsuranceCompanies();
            }
            if (count($insuranceCompanies) > 0)
                return $this->returnData('companies', $insuranceCompanies);

            return $this->returnError('E001', trans('messages.There are no insurance companies found'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

}

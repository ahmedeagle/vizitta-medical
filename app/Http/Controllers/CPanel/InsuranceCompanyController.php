<?php

namespace App\Http\Controllers\CPanel;

use App\Models\InsuranceCompany;
use App\Traits\Dashboard\InsuranceCompanyTrait;
use App\Traits\Dashboard\PublicTrait;
use App\Traits\CPanel\GeneralTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\CPanel\InsuranceCompanyResource;
use Illuminate\Support\Facades\Validator;

class InsuranceCompanyController extends Controller
{
    use InsuranceCompanyTrait, PublicTrait, GeneralTrait;

    public function index()
    {
        try {
            $insuranceCompanies = InsuranceCompany::paginate(PAGINATION_COUNT);
            $result = new InsuranceCompanyResource($insuranceCompanies);
            return response()->json(['status' => true, 'data' => $result]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function show(Request $request)
    {
        try {
            $company = $this->getInsuranceCompanyById($request->id);
            if ($company == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);
            /*else
                $result = new InsuranceCompanyResource($company);*/

            return response()->json(['status' => true, 'data' => $company]);
        } catch (\Exception $ex) {
            return response()->json([
                'success' => false,
                'error' => __('main.oops_error'),
            ], 200);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
                "status" => "required|boolean",
            ]);
            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }
            $path = "";
            if (isset($request->image)) {
                $path = $this->saveImage('insurance', $request->image);
            }
            $company = $this->createInsuranceCompany($request);
            $company->update([
                'image' => $path
            ]);
            return response()->json(['status' => true, 'msg' => __('main.insurance_company_added_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
                "status" => "required|boolean",
            ]);
            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }
            $company = $this->getCompanyById($request->id);
            if ($company == null)
                return response()->json(['success' => true, 'data' => [], 'msg' => __('main.not_found')], 200);

            $path = $company->image;
            if (isset($request->image)) {
                $path = $this->saveImage('insurance', $request->image);
            }
            $this->updateInsuranceCompany($company, $request);
            $company->update([
                'image' => $path
            ]);
            return response()->json(['status' => true, 'msg' => __('main.insurance_company_updated_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $company = $this->getCompanyById($request->id);
            if ($company == null)
                return response()->json(['success' => true, 'data' => [], 'msg' => __('main.not_found')], 200);

            if (count($company->doctors) == 0) {
                $company->delete();
                return response()->json(['status' => true, 'msg' => __('main.insurance_company_deleted_successfully')]);
            } else {
                return response()->json(['status' => true, 'msg' => __('main.insurance_company_cannot_deleted')]);
            }
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function changeStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "id" => "required",
                "status" => "required",
            ]);
            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            $company = $this->getCompanyById($request->id);
            if ($company == null)
                return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);

            if ($request->status != 0 && $request->status != 1) {
                return response()->json(['status' => false, 'error' => __('main.enter_valid_activation_code')], 200);
            } else {
                $this->changeCompanyStatus($company, $request->status);
                return response()->json(['status' => true, 'msg' => __('main.insurance_company_status_changed_successfully')]);
            }
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

}

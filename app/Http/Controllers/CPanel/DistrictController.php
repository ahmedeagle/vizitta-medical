<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Resources\CPanel\DistrictResource;
use App\Models\District;
use App\Traits\Dashboard\PublicTrait;
use App\Traits\CPanel\GeneralTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Dashboard\DistrictTrait;
use Illuminate\Support\Facades\Validator;

class DistrictController extends Controller
{
    use DistrictTrait, PublicTrait, GeneralTrait;

    public function index()
    {
        $cities = District::with('city')->paginate(PAGINATION_COUNT);
        $result = new DistrictResource($cities);
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function create()
    {
        $cities = $this->getCities();
        return response()->json(['status' => true, 'data' => $cities]);
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
                "city_id" => "required|numeric"
            ]);

            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }
            $this->createDistrict($request);
            return response()->json(['status' => true, 'msg' => __('main.district_added_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function edit(Request $request)
    {
        try {
            $district = $this->getDistrictById($request->id);
            if ($district == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $cities = $this->getCities();
            $result['district'] = $district;
            $result['cities'] = $cities;
            return response()->json(['status' => true, 'data' => $result]);
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
                "city_id" => "required|numeric"
            ]);
            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }
            $district = $this->getDistrictById($request->id);
            if ($district == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $this->updateDistrict($district, $request);
            return response()->json(['status' => true, 'msg' => __('main.district_updated_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $district = $this->getDistrictById($request->id);
            if ($district == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            if (count($district->providers) == 0) {
                $district->delete();
                return response()->json(['status' => true, 'msg' => __('main.district_deleted_successfully')]);
            } else {
                return response()->json(['success' => false, 'error' => __('main.district_with_providers_cannot_be_deleted')], 200);
            }
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

}

<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\District;
use App\Traits\GlobalTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CityController extends Controller
{
    use GlobalTrait;

    public function index()
    {
        try {
            $cities = $this->getAllCities();
            if (count($cities) > 0)
                return $this->returnData('cities', $cities);

            return $this->returnError('E001', trans('messages.There are no cities found'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }

    public function getDistricts(Request $request)
    {
        try {
            $cityID = null;
            if (isset($request->city_id)) {
                $districts = District::select('id', DB::raw('name_' . app()->getLocale() . ' as name'))
                ->where('city_id',$request->city_id )->get();

                //if ($city == null)
                  //  return $this->returnError('E001', trans('messages.There are no city with this id'));

                if (count($districts) > 0)
                    return $this->returnData('districts', $districts);
            }

            return $this->returnError('E001', trans('messages.There are no districts found'));
        } catch (\Exception $ex) {
            return $this->returnError($ex->getCode(), $ex->getMessage());
        }
    }
}

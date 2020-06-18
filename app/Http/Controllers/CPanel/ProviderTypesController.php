<?php

namespace App\Http\Controllers\CPanel;

use App\Models\Provider;
use App\Models\ProviderType;
use App\Traits\Dashboard\ProviderTypesTrait;
use App\Traits\CPanel\GeneralTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ProviderTypesController extends Controller
{
    use ProviderTypesTrait, GeneralTrait;

    public function index()
    {
        $result = ProviderType::paginate(PAGINATION_COUNT);
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "name_en" => "required|max:255",
                "name_ar" => "required|max:255",
            ]);
            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }
            $this->createProviderType($request);
            return response()->json(['status' => true, 'msg' => __('main.provider_type_added_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function edit(Request $request)
    {
        try {
            $type = $this->getProviderTypeById($request->id);
            if ($type == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            return response()->json(['status' => true, 'data' => $type]);
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
            ]);
            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }
            $types = $this->getProviderTypeById($request->id);
            if ($types == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $this->updateProviderType($types, $request);
            return response()->json(['status' => true, 'msg' => __('main.provider_type_updated_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }


    public function delete(Request $request)
    {

        $validator = Validator::make($request->all(), [
            "id" => "required|exists:provider_types,id",
        ]);
        if ($validator->fails()) {
            $code = $this->returnCodeAccordingToInput($validator);
            return $this->returnValidationError($code, $validator);
        }

        $checkIfTypeUsedByProviders = Provider::where('type_id', $request->id)->count();

        if ($checkIfTypeUsedByProviders > 0) {
            return $this->returnError('E001', __('messages.can not delete this item'));
        }

        ProviderType::where('id', $request->id)->delete();

        return $this->returnSuccessMessage(__('messages.item deleted successfully'));
    }

}

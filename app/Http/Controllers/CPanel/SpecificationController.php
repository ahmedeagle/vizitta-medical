<?php

namespace App\Http\Controllers\CPanel;

use App\Models\Specification;
use App\Traits\Dashboard\SpecificationTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class SpecificationController extends Controller
{
    use SpecificationTrait;

    public function index()
    {
        $result = Specification::paginate(PAGINATION_COUNT);
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
            $this->createSpecification($request);
            return response()->json(['status' => true, 'msg' => __('main.doctors_specifications_added_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function edit(Request $request)
    {
        try {
            $specification = $this->getSpecificationById($request->id);
            if ($specification == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            return response()->json(['status' => true, 'data' => $specification]);
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
            $specification = $this->getSpecificationById($request->id);
            if ($specification == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $this->updateSpecification($specification, $request);
            return response()->json(['status' => true, 'msg' => __('main.doctors_specifications_updated_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $specification = $this->getSpecificationById($request->id);
            if ($specification == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            if (count($specification->doctors) == 0) {
                $specification->delete();
                return response()->json(['status' => true, 'msg' => __('main.doctor_specification_deleted_successfully')]);
            } else {
                return response()->json(['success' => false, 'error' => __('main.doctor_specification_with_doctors_cannot_be_deleted')], 200);
            }
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

}

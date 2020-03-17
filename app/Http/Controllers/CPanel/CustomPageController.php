<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Resources\CPanel\CustomPageResource;
use App\Models\CustomPage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Dashboard\CustomPageTrait;
use Illuminate\Support\Facades\Validator;

class CustomPageController extends Controller
{
    use CustomPageTrait;

    public function index()
    {
        $pages = CustomPage::paginate(PAGINATION_COUNT);
        $result = new CustomPageResource($pages);
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "title_en" => "required|max:255",
                "title_ar" => "required|max:255",
                "content_en" => "required",
                "content_ar" => "required",
            ]);
            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }
            $this->createCustomPage($request);
            return response()->json(['status' => true, 'msg' => __('main.custom_page_added_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function edit(Request $request)
    {
        try {
            $customPage = $this->getCustomPageById($request->id);
            if ($customPage == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            return response()->json(['status' => true, 'data' => $customPage]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "title_en" => "required|max:255",
                "title_ar" => "required|max:255",
                "content_en" => "required",
                "content_ar" => "required",
            ]);
            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }
            $customPage = $this->getCustomPageById($request->id);
            if ($customPage == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $this->updateCustomPage($customPage, $request);
            return response()->json(['status' => true, 'msg' => __('main.custom_page_updated_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $customPage = $this->getCustomPageById($request->id);
            if ($customPage == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $customPage->delete();
            return response()->json(['status' => true, 'msg' => __('main.custom_page_deleted_successfully')]);
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

            $customPage = $this->getCustomPageById($request->id);
            if ($customPage == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            if ($request->status != 0 && $request->status != 1) {
                return response()->json(['status' => false, 'error' => __('main.enter_valid_activation_code')], 200);
            } else {
                $this->changerCustomPageStatus($customPage, $request->status);
                return response()->json(['status' => true, 'msg' => __('main.custom_page_status_changed_successfully')]);
            }
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

}

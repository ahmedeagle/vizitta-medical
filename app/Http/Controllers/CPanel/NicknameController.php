<?php

namespace App\Http\Controllers\CPanel;

use App\Models\Nickname;
use App\Traits\Dashboard\NicknameTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class NicknameController extends Controller
{
    use NicknameTrait;

    public function index()
    {
        $result = Nickname::paginate(PAGINATION_COUNT);
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
            $this->createNickname($request);
            return response()->json(['status' => true, 'msg' => __('main.nicknames_added_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function edit(Request $request)
    {
        try {
            $nickname = $this->getNicknameById($request->id);
            if ($nickname == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            return response()->json(['status' => true, 'data' => $nickname]);
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
            $nickname = $this->getNicknameById($request->id);
            if ($nickname == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $this->updateNickname($nickname, $request);
            return response()->json(['status' => true, 'msg' => __('main.nicknames_updated_successfully')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $nickname = $this->getNicknameById($request->id);
            if ($nickname == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            if (count($nickname->doctors) == 0) {
                $nickname->delete();
                return response()->json(['status' => true, 'msg' => __('main.nickname_deleted_successfully')]);
            } else {
                return response()->json(['success' => false, 'error' => __('main.nickname_with_doctors_cannot_be_deleted')], 200);
            }
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

}

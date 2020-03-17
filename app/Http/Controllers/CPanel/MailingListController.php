<?php

namespace App\Http\Controllers\CPanel;

use App\Models\Subscribtion;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MailingListController extends Controller
{
    public function index()
    {
        try {
            $result = Subscribtion::paginate(PAGINATION_COUNT);
            return response()->json(['status' => true, 'data' => $result]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $subscribe = Subscribtion::find($request->id);
            if ($subscribe == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $subscribe->delete();
            return response()->json(['status' => true, 'msg' => __('main.deletion_successful')]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }
}

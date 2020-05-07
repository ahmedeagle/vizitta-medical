<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Resources\CPanel\CommentsResource;
use App\Http\Resources\CPanel\ReportsResource;
use App\Models\CommentReport;
use App\Models\Reservation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\Dashboard\CommentsTrait;
use MercurySeries\Flashy\Flashy;

class CommentsController extends Controller
{
    use CommentsTrait;

    public function index()
    {
        $comments = Reservation::whereNotNull('user_id')->whereNotNull('rate_comment')->where('rate_comment', '!=', '')->select('*')->paginate(PAGINATION_COUNT);
        $result = new CommentsResource($comments);
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function getReportsData()
    {
        $reports = CommentReport::orderBy('created_at', 'DESC')->paginate(PAGINATION_COUNT);
        $result = new ReportsResource($reports);
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function destroy(Request $request)
    {
        $reservation = Reservation::where("id", $request->id)->first();
        if (!$reservation) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }

        $reservation->update([
            'doctor_rate' => null,
            'provider_rate' => null,
            'rate_comment' => '',
            'rate_date' => null
        ]);
        return response()->json(['status' => true, 'msg' => __('main.operation_done_successfully')]);
    }


    public function deleteReport(Request $request)
    {
        $report = CommentReport::findOrFail($request->id);
        $report->delete();
        return response()->json(['status' => true, 'msg' => __('main.operation_done_successfully')]);
    }


    public function update(Request $request)
    {
        $requestData = $request->only(['doctor_rate', 'provider_rate', 'reservation_id', 'rate_comment']);
        try {
            $validator = Validator::make($requestData, [
                "doctor_rate" => "required|numeric|min:1|max:5",
                "provider_rate" => "required|numeric|min:1|max:5",
                "reservation_id" => "required|exists:reservations,id",
                "rate_comment" => "required|max:200",
            ]);

            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            $reservation = Reservation::find($request->reservation_id);
            $reservation->update([
                'doctor_rate' => $request->doctor_rate,
                'provider_rate' => $request->provider_rate,
                'rate_comment' => $request->rate_comment,
            ]);

            return response()->json(['status' => true, 'msg' => __('main.comment_rate_updated_successfully')]);

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

}

<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\CommentReport;
use App\Models\GeneralNotification;
use App\Models\Notification;
use App\Models\Provider;
use App\Models\Reciever;
use App\Models\Reservation;
use Freshbitsweb\Laratables\Laratables;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use DB;
use Validator;
use App\Traits\Dashboard\CommentsTrait;
use MercurySeries\Flashy\Flashy;
use Vinkla\Hashids\Facades\Hashids;

class CommentsController extends Controller
{
    use CommentsTrait;


    public function getData()
    {
        try {
            return $this->getAllComments();
        } catch (\Exception $ex) {
            return abort('404');
        }
    }

    public function getreportsData()
    {
        return $this->getAllReports();
    }

    public function index()
    {
        if (request('notification')) {  // mark as read
            GeneralNotification::where('seen', '0')
                ->where('id', Hashids::decode(request('notification')))
                ->update(['seen' => '1']);
        }

        $reservations = Reservation::select('id', 'doctor_rate', 'provider_rate', 'rate_comment', 'rate_date')->get();
        return view('comments.index', compact('reservations'));
    }

    public function reports()
    {
        return view('reports.index');
    }

    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "doctor_rate" => "required|numeric|min:1|max:5",
                "provider_rate" => "required|numeric|min:1|max:5",
                "reservation_id" => "required|exists:reservations,id",
                "rate_comment" => "required|max:200",
            ]);

            if ($validator->fails()) {
                Flashy::error($validator->errors()->first());
                return redirect()->back()->withErrors($validator)->withInput($request->all())->with(['commentModalId' => $request->reservation_id]);
            }

            $reservation = Reservation::find($request->reservation_id);
            $reservation->update([
                'doctor_rate' => $request->doctor_rate,
                'provider_rate' => $request->provider_rate,
                'rate_comment' => $request->rate_comment,
            ]);
            Flashy::success('تمت  تعديل التقييم  بنجاح');
            return redirect()->route('admin.comments');
        } catch (\Exception $ex) {
            return $ex;
        }
    }

    public function delete($id)
    {
        $reservation = Reservation::where("id", $id)->first();
        if (!$reservation) {
            Flashy::error('حدث خطأ برجاء المحاولة مرة اخرى');
            return redirect()->back()->with("error", "حدث خطأ برجاء المحاولة مرة اخرى");
        }

        $reservation->update([
            'doctor_rate' => null,
            'provider_rate' => null,
            'rate_comment' => '',
            'rate_date' => null
        ]);
        Flashy::success('تمت العملية بنجاح');
        return redirect()->route('admin.comments');
    }


    public function deletereport($id)
    {
        $report = CommentReport::findOrFail($id);
        $report->delete();
        Flashy::success('تمت العملية بنجاح');
        return redirect()->back();
    }
}

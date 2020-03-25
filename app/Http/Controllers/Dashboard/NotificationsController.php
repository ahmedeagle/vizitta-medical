<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\GeneralNotification;
use App\Models\Notification;
use App\Models\Provider;
use App\Models\Reciever;
use Carbon\Carbon;
use Freshbitsweb\Laratables\Laratables;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use DB;
use Validator;
use MercurySeries\Flashy\Flashy;

class NotificationsController extends Controller
{

    function __construct()
    {

    }

    public function index($type)
    {

        if ($type != 'users' && $type != 'providers') {
            return redirect()->route('home');
        }
        return view('notifications.index')->with('type', $type);
    }

    public function getData($type)
    {
        $relation = 'user';
        if ($type == 'users') {
            $relation = 'user';
        }
        if ($type == 'providers') {

            $relation = 'provider';
        }
        return Laratables::recordsOf(Notification::class, function ($query) use ($type, $relation) {
            return $query->with(['recievers' => function ($q) use ($relation) {
                $q->select("*");
                $q->with([$relation]);
            }])->where('type', $type)->select('*');
        });
    }

    public function getRecieversData($notifyId, $type)
    {


        $receivers = Reciever::where('notification_id', $notifyId)->pluck('actor_id');
        $relation = 'User';
        if ($type == 'users') {
            return Laratables::recordsOf(User::class, function ($query) use ($receivers) {
                return $query->whereIn('id', $receivers);
            });
        } else {
            return Laratables::recordsOf(Provider::class, function ($query) use ($receivers) {
                return $query->whereIn('id', $receivers);
            });
        }
    }

    public function getRecievers($notifyId, $type)
    {
        $notification = Notification::find($notifyId);
        if (!$notification)
            return abort('404');

        return view('notifications.receivers')->with(['notifyId' => $notifyId, 'type' => $type]);
    }

    public function get_add($type)
    {
        if ($type == "users") {
            $data['receivers'] = User::select('id', 'name')->get();
        } else {
            $data['receivers'] = Provider::select('id', 'name_ar as name')->get();
        }
        $data['type'] = $type;
        return view("notifications.add", $data);
    }

    public
    function post_add(Request $request)
    {


        $validator = Validator::make($request->all(), [
            "title" => "required|max:255",
            "content" => "required|max:255",
            "notify-type" => "required|in:1,2",
        ]);

        if ($validator->fails()) {
            Flashy::error('يوجد خطأ, الرجاء التأكد من إدخال جميع الحقول');
            return redirect()->back()->withErrors($validator)->withInput($request->all());
        }


        $title = $request->input("title");
        $content = $request->input("content");
        $option = $request->input("notify-type");
        $type = $request->input("type");

        if ($option == 2) {
            if ($request->has('ids')) {
                if (!is_array($request->ids) || count($request->ids) == 0) {
                    return redirect()->back()->withErrors(['receivers' => 'يجب اختيار مستخدم علي الاقل '])->withInput($request->all);
                }
            } else {
                return redirect()->back()->withErrors(['receivers' => 'يجب اختيار مستخدم علي الاقل '])->withInput($request->all);
            }
        }


        if ($type == "users") {
            if ($option == 2) {
                $actors = User::whereIn("id", $request->ids)
                    ->select("id", "device_token")
                    ->get();
            } else {
                $actors = User::get();
            }
        } else {
            if ($option == 2) {
                $actors = Provider::whereIn("id", $request->ids)
                    ->select("id", "device_token", "web_token")
                    ->get();
            } else {
                $actors = Provider::get();
            }
        }

        $notify_id = Notification::insertGetId([
            "title" => $title,
            "content" => $content,
            "type" => $type
        ]);

        foreach ($actors as $actor) {

            $actor->makeVisible(['device_token', 'web_token']);
            if ($type == "users") {

                Reciever::insert([
                    "notification_id" => $notify_id,
                    "actor_id" => $actor->id
                ]);
                // push notification
                if ($actor->device_token != null) {
                    //send push notification
                    (new \App\Http\Controllers\NotificationController(['title' => $title, 'body' => $content]))->sendUser(User::find($actor->id));
                }

            } elseif ($type == "providers") {

                Reciever::insert([
                    "notification_id" => $notify_id,
                    "actor_id" => $actor->id,

                ]);
                // push notification
                if ($actor->device_token != null) {
                    (new \App\Http\Controllers\NotificationController(['title' => $title, 'body' => $content]))->sendProvider(Provider::find($actor->id));
                }

                if ($actor->web_token != null) {
                    (new \App\Http\Controllers\NotificationController(['title' => $title, 'body' => $content]))->sendProviderWeb(Provider::find($actor->id));
                }

            }
        }

        Flashy::success('تمت العملية بنجاح');
        return redirect()->back();


    }

    public
    function delete($id)
    {
        $data = Notification::where("id", $id)->first();
        if ($data) {
            $type = $data->type;
            $data->where("id", $id)->delete();
            Reciever::where("notification_id", $id)->delete();
            Flashy::success('تمت العملية بنجاح');
            return redirect()->route('admin.notifications', $type);
        } else {
            Flashy::error('حدث خطأ برجاء المحاولة مرة اخرى');
            return redirect()->back()->with("error", "حدث خطأ برجاء المحاولة مرة اخرى");
        }
    }

    public function notificationCenter()
    {
        $status = 'all';
        $list = ['all', 'read', 'unread'];

        if (request('status')) {
            if (!in_array(request('status'), $list)) {
                $data['notifications'] = $this->geNotificationByStatus();
            } else {
                $status = request('status') ? request('status') : $status;
                $data['notifications'] = $this->geNotificationByStatus($status);
            }
            return view('notifications.notifications', $data);
        } else {
            $data['notifications'] = GeneralNotification::orderBy('id', 'DESC')
                ->paginate(20);
        }
        return view('notifications.notifications', $data);
    }

    private function geNotificationByStatus($status = 'all')
    {
        if ($status == 'read') {
            return $notifications = GeneralNotification::where('seen', '=', '1')->orderBy('id', 'DESC')->paginate(20);
        } elseif ($status == 'unread') {
            return $notifications = GeneralNotification::where('seen', '=', '0')->orderBy('id', 'DESC')->paginate(20);
        } else {
            return $notifications = GeneralNotification::orderBy('id', 'DESC')->paginate(20);
        }

    }


}

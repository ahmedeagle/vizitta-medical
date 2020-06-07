<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Resources\CPanel\NotificationReceiversResource;
use App\Jobs\SenAdminNotification;
use App\Models\GeneralNotification;
use App\Models\Notification;
use App\Models\Provider;
use App\Models\Reciever;
use Carbon\Carbon;
use Freshbitsweb\Laratables\Laratables;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use MercurySeries\Flashy\Flashy;

class NotificationsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $relation = 'user';
            if ($request->type == 'users') {
                $relation = 'user';
            }
            if ($request->type == 'providers') {
                $relation = 'provider';
            }

            /*$notifications = Notification::with(['recievers' => function ($q) use ($relation) {
                $q->select("*");
                $q->with([$relation]);
            }])->where('type', $request->type)->select('*')->get();*/

            $notifications = Notification::where('type', $request->type)->select('id', 'title', 'content', 'created_at')->paginate(PAGINATION_COUNT);
            return response()->json(['status' => true, 'data' => $notifications]);

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function show(Request $request)
    {
        $receivers = Reciever::where('notification_id', $request->notifyId)->pluck('actor_id');
//        $relation = 'User';
        if ($request->type == 'users') {
            $result = User::whereIn('id', $receivers)->get(['id', 'name', 'mobile']);
        } else {
            $result = Provider::whereIn('id', $receivers)->get(['id', 'name_ar', 'name_en', 'mobile']);
        }
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function getReceivers(Request $request)
    {
        $relation = 'user';
        if ($request->type == 'users')
            $relation = 'user';
        elseif ($request->type == 'providers')
            $relation = 'provider';

        $receivers = Notification::with(['recievers' => function ($q) use ($relation) {
            $q->select("*");
            $q->with([$relation]);
        }])->where('type', $request->type)->find($request->notifyId);

        if (!$receivers)
            return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

        $result = new NotificationReceiversResource($receivers);
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function create(Request $request)
    {
        if ($request->type == "users") {
            $result['receivers'] = User::select('id', 'name')->get();
        } else {
            $result['receivers'] = Provider::select('id', 'name_ar as name')->get();
        }
        $result['type'] = $request->type;
        return response()->json(['status' => true, 'data' => $result]);
    }




    // make job and queue  for ignore respose timeout
    public function store(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                "title" => "required|max:255",
                "content" => "required|max:255",
                "notify-type" => "required|in:1,2",
            ]);

            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            $title = $request->input("title");
            $content = $request->input("content");
            $option = $request->input("notify-type");
            $type = $request->input("type");

            if ($option == 2) {
                if ($request->has('ids')) {
                    if (!is_array($request->ids) || count($request->ids) == 0) {
                        return response()->json(['status' => false, 'error' => ['receivers' => __('main.at_least_one_user_must_be_selected')]], 200);
                    }
                } else {
                    return response()->json(['status' => false, 'error' => ['receivers' => __('main.at_least_one_user_must_be_selected')]], 200);
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

            dispatch(new SenAdminNotification($actors,$type,$notify_id,$title,$content)) ->delay(now()->addMinutes(1));;

          /*  foreach ($actors as $actor) {

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
            }*/

            return response()->json(['status' => true, 'msg' => __('main.operation_done_successfully')]);

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }

    }

    public function destroy(Request $request)
    {
        $data = Notification::where("id", $request->id)->first();
        if ($data) {
            $type = $data->type;
            $data->where("id", $request->id)->delete();
            Reciever::where("notification_id", $request->id)->delete();

            return response()->json(['status' => true, 'data' => $type, 'msg' => __('main.operation_done_successfully')]);
        } else {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    ################### Start to get un read notifications ##############################
    public function getHeaderNotifications(Request $request)
    {
        try {
            $query = GeneralNotification::query();

            if ($request->type == 'read')
                $sql = $query->where('seen', '1');
            elseif ($request->type == 'unread')
                $sql = $query->where('seen', '0');
            else
                $sql = $query;

            $notifications = $sql->selection()->addSelect('id', 'seen', 'data_id', 'type')->paginate(PAGINATION_COUNT);
            return response()->json(['status' => true, 'data' => $notifications]);

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }
    ################### End to get un read notifications ##############################

    ################### Start to read notification ##############################
    public function readNotification(Request $request)
    {
        try {
            $notification = GeneralNotification::find($request->id);

            if (!$notification)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            if ($notification->seen == '0')
                $notification->update(['seen' => '1']);

            return response()->json(['status' => true, 'msg' => __('main.read_notification_successfully')]);

        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }
    ################### End to read notification ##############################
}

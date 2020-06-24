<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Resources\CPanel\NotificationReceiversResource;
use App\Jobs\SenAdminNotification;
use App\Models\GeneralNotification;
use App\Models\Notification;
use App\Models\Offer;
use App\Models\OfferCategory;
use App\Models\Provider;
use App\Models\Reciever;
use App\Models\Specification;
use App\Traits\Dashboard\PublicTrait;
use App\Traits\GlobalTrait;
use Carbon\Carbon;
use Freshbitsweb\Laratables\Laratables;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use MercurySeries\Flashy\Flashy;
use Illuminate\Support\Facades\Artisan;

class NotificationsController extends Controller
{
    use GlobalTrait;

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

            $notifications = Notification::where('type', $request->type)
                ->select('id', 'title', 'photo', 'content', 'created_at')
                ->orderBy('id', 'DESC')->paginate(PAGINATION_COUNT);
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
                "allow_fire_base" => 'required|in:0,1',
                "direction_type" => "required|in:offer,category,center,branch,consulting,external,none",

            ]);

            if ($validator->fails()) {
                $result = $validator->messages()->toArray();
                return response()->json(['status' => false, 'error' => $result], 200);
            }

            $title = $request->input("title");
            $content = $request->input("content");
            $option = $request->input("notify-type");
            $type = $request->input("type");
            $allow_fire_base = $request->input("allow_fire_base");


            ////////// determine  which place notification will go after user click on it ////////

            if ($request->direction_type == 'external') {
                if (empty($request->external_link)) {
                    return $this->returnError('D000', __('messages.external link required'));
                }
            }
            if ($request->direction_type == 'branch') {
                if ((empty($request->branch_id) or !is_numeric($request->branch_id)) && ($request->branch_id != 0)) {
                    return $this->returnError('D000', __('messages.provider id required'));
                }
                //check if  branch is exists or not
                $branch = Provider::whereNotNull('provider_id')->where('id', $request->branch_id)->first();
                if (!$branch) {
                    return $this->returnError('D000', __('messages.branch not found'));
                }
                // required   subcategory_id    1 -> doctors 2 -> services
                if ((empty($request->subcategory_id) or !is_numeric($request->subcategory_id)) && ($request->subcategory_id != 0)) {
                    return $this->returnError('D000', __('messages.subcategory required'));
                }
                if ($request->subcategory_id != 1 && $request->subcategory_id != 2) {
                    return $this->returnError('D000', __('messages.subcategory_id required and must be 1 for doctors 2 for services'));
                }
            }
            if ($request->type == 'center') {
                //nothing
            }
            if ($request->direction_type == 'consulting') {
                // required only if category_id  not equal 0  //i.e not all categories then we need subcategory of this category
                if ((empty($request->subcategory_id) or !is_numeric($request->subcategory_id)) && ($request->subcategory_id != 0)) {
                    return $this->returnError('D000', __('messages.subcategory required'));
                }
                //check if subcategory exists
                if ($request->has('subcategory_id')) {
                    $specification = Specification::where('id', $request->subcategory_id)->first();

                    if (!$specification && $request->subcategory_id != 0) {
                        return $this->returnError('D000', __('messages.subcategory not found'));
                    }
                }


            }
            if ($request->direction_type == 'category') {
                // 0 -> means all category of offers    otherwise mean offer category id
                if ((empty($request->category_id) or !is_numeric($request->category_id)) && ($request->category_id != 0)) {
                    return $this->returnError('D000', __('messages.category required'));
                }

                //check if main category not equal 0 (i.e not all categroy)  we must check if this main category exists or not
                if ($request->category_id != 0) {
                    $category = OfferCategory::whereNull('parent_id')->where('id', $request->category_id)->first();
                    if (!$category) {
                        return $this->returnError('D000', __('messages.category not found'));
                    }
                    // required only if category_id  not equal 0  //i.e not all categories then we need subcategory of this category
                    if ((empty($request->subcategory_id) or !is_numeric($request->subcategory_id)) && ($request->subcategory_id != 0)) {
                        return $this->returnError('D000', __('messages.subcategory required'));
                    }
                }

                //check if subcategory exists
                if ($request->has('subcategory_id')) {
                    if ($request->subcategory_id != 0) {
                        $subCategory = OfferCategory::whereNotNull('parent_id')->where('id', $request->subcategory_id)->first();
                        if (!$subCategory) {
                            return $this->returnError('D000', __('messages.subcategory not found'));
                        }
                    }
                }

            }
            if ($request->direction_type == 'offer') {
                if (empty($request->offer_id) or !is_numeric($request->offer_id)) {
                    return $this->returnError('D000', __('messages.offer required'));
                }
                $offer = Offer::where('id', $request->offer_id)->first();   // offer subcategory
                if (!$offer)
                    return $this->returnError('D000', __('messages.offer not found'));
            }


            if ($request->direction_type == 'category') {
                $id = $request->category_id;
                $notifictionable_type = 'App\Models\OfferCategory';
            } elseif ($request->direction_type == 'offer') {
                $id = $request->offer_id;
                $notifictionable_type = 'App\Models\Offer';
            } elseif ($request->direction_type == 'branch') {
                $id = $request->branch_id;
                $notifictionable_type = 'App\Models\Provider';
            } elseif ($request->direction_type == 'center') {
                $id = 0;
                $notifictionable_type = 'App\Models\MedicalCenter';
            } elseif ($request->direction_type == 'consulting') {
                $id = 0;
                $notifictionable_type = 'App\Models\Doctor';
            } elseif ($request->direction_type == "external") {
                $id = null;
                $notifictionable_type = 'external';
            } else {
                $id = null;
                $notifictionable_type = 'none';
            }

            ////////////////////////////////////////////////////////////////
            $fileName = "";
            if (isset($request->photo) && !empty($request->photo)) {
                $fileName = $this->saveImage('notifications', $request->photo);
            }

            $notify_id = Notification::insertGetId([
                "title" => $title,
                "content" => $content,
                "type" => $type,
                "photo" => $fileName,
                'allow_fire_base' => $allow_fire_base,
                'notifictionable_type' => $notifictionable_type,
                'notifictionable_id' => $id,
                'subCategory_id' => isset($request->subcategory_id) ? $request->subcategory_id : 0,
                'external_link' => isset($request->external_link) ? $request->external_link : null
            ]);


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
                if ($option == 1) {
                    User::whereNotNull('device_token')
                        ->whereIn("id", $request->ids)
                        ->select("id", "device_token")
                        ->chunk(50, function ($actors) use ($notify_id, $content, $title, $type, $allow_fire_base) {
                            $this->sendActorNotification($actors, $notify_id, $content, $title, $type, $allow_fire_base);
                        });

                } else {
                    User::whereNotNull('device_token')
                        ->select('device_token', 'id')
                        ->chunk(50, function ($actors) use ($notify_id, $content, $title, $type, $allow_fire_base) {
                            $this->sendActorNotification($actors, $notify_id, $content, $title, $type, $allow_fire_base);

                        });
                }
            } else {
                if ($option == 1) {
                    Provider::whereNotNull('device_token')
                        ->whereIn("id", $request->ids)
                        ->select("id", "device_token", "web_token")
                        ->chunk(50, function ($actors) use ($notify_id, $content, $title, $type, $allow_fire_base) {
                            $this->sendActorNotification($actors, $notify_id, $content, $title, $type, $allow_fire_base);
                        });
                } else {
                    Provider::whereNotNull('device_token')
                        ->select('device_token', 'web_token', 'id')
                        ->chunk(50, function ($actors) use ($notify_id, $content, $title, $type, $allow_fire_base) {
                            $this->sendActorNotification($actors, $notify_id, $content, $title, $type, $allow_fire_base);
                        });
                }
            }


            return response()->json(['status' => true, 'msg' => __('messages.will send notify')]);

        } catch (\Exception $ex) {

            return $ex;
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

            $notifications = $sql->selection()->addSelect('id', 'seen', 'data_id', 'type')
                ->orderBy('id','DESC')
                ->paginate(PAGINATION_COUNT);
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
    private function sendActorNotification($actors, $notify_id, $content, $title, $type, $allow_fire_base)
    {
        dispatch(new SenAdminNotification($actors, $notify_id, $content, $title, $type, $allow_fire_base));
    }
}

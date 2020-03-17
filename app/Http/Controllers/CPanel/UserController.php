<?php

namespace App\Http\Controllers\CPanel;

use App\Http\Resources\CPanel\UserResource;
use App\Models\User;
use App\Traits\Dashboard\UserTrait;
use App\Traits\CPanel\GeneralTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    use UserTrait, GeneralTrait;

    public function index()
    {
        $queryStr = '';
        if (request('queryStr')) {
            $queryStr = request('queryStr');
        }

        $users = User::where(function ($q) use ($queryStr) {
            return $q->where('name', 'LIKE', '%' . trim($queryStr) . '%');
        })->paginate(PAGINATION_COUNT);

        $result = new UserResource($users);
        return response()->json(['status' => true, 'data' => $result]);
    }

    public function show(Request $request)
    {
        try {
            $user = $this->getUserById($request->id);
            if ($user == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            $result['user'] = $user;
            $result['reservations'] = $this->getCustomUserReservations($user->id);
            $result['records'] = $this->getCustomUserRecords($user->id);
            $result['favoriteDoctors'] = $this->getCustomUserFavoriteDoctors($user->id);
            $result['favoriteProviders'] = $this->getCustomUserFavoriteProviders($user->id);
            return response()->json(['status' => true, 'data' => $result]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $user = $this->getUserById($request->id);
            if ($user == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            if (count($user->reservations) > 0) {
                return response()->json(['success' => false, 'error' => __('main.user_with_reservations_cannot_be_deleted')], 200);
            } else {
                return response()->json(['status' => true, 'msg' => __('main.user_deleted_successfully')]);
            }
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

    public function changeStatus(Request $request)
    {
        try {
            $user = $this->getUserById($request->id);
            if ($user == null)
                return response()->json(['success' => false, 'error' => __('main.not_found')], 200);

            if ($request->status != 0 && $request->status != 1) {
                return response()->json(['status' => false, 'error' => __('main.enter_valid_activation_code')], 200);
            } else {
                $this->changerUserStatus($user, $request->status);
                return response()->json(['status' => true, 'msg' => __('main.user_status_changed_successfully')]);
            }
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'error' => __('main.oops_error')], 200);
        }
    }

}




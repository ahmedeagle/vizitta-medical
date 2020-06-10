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
         if (request('queryStr')) {  //search only by name
             $queryStr = request('queryStr');
            $users = User::where('name', 'LIKE', '%' . trim($queryStr) . '%')->orderBy('id', 'DESC')->paginate(10);
        } elseif (request('generalQueryStr')) {  //search all column
            $q = request('generalQueryStr');
            $users = User::where('name', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('mobile', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('id_number', 'LIKE', '%' . trim($q) . '%')
                ->orWhere('birth_date', 'LIKE binary', '%' . trim($q) . '%')
                ->orWhere('created_at', 'LIKE binary', '%' . trim($q) . '%')
                ->orWhereHas('city', function ($query) use ($q) {
                    $query->where('name_ar', 'LIKE', '%' . trim($q) . '%');
                })->orWhereHas('insuranceCompany', function ($query) use ($q) {
                    $query->where('name_ar', 'LIKE', '%' . trim($q) . '%');
                })->orWhereHas('point', function ($query) use ($q) {
                    $query->where('points', '=', trim($q));
                })
                ->orderBy('id', 'DESC')
                ->paginate(PAGINATION_COUNT);
        } else
            $users = User::orderBy('id', 'DESC')->paginate(PAGINATION_COUNT);

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




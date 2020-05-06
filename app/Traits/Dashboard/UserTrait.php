<?php

namespace App\Traits\Dashboard;

use App\Models\AdminPermission;
use App\Models\Favourite;
use App\Models\Manager;
use App\Models\Reservation;
use App\Models\User;
use App\Models\UserRecord;
use Freshbitsweb\Laratables\Laratables;
use DB;

trait UserTrait
{
    public function getUserById($id){
        return User::find($id);
    }

    public function getAllUsers($queryStr){
        return Laratables::recordsOf(User::class, function ($query) use($queryStr) {
            return $query -> where(function($q) use($queryStr){
                return $q ->where('name', 'LIKE', '%' . trim($queryStr) . '%');
            });
        });
    }

    public function getAllAdmins(){
        return Laratables::recordsOf(Manager::class,function ($query){
               return $query -> orderBy('id','DESC');
        });
    }

    public function getAdminById($id)
    {
//        return Manager::with('permissions')-> find($id) ;
        return Manager::find($id) ;
    }

    public function getUserReservations($id){
        return Reservation::where('user_id', $id)->orderBy('day_date')->orderBy('from_time')->get();
    }

    public function getUserRecords($id){
        return UserRecord::where('user_id', $id)->orderBy('day_date')->orderBy('created_at')->get();
    }

    public function getUserFavoriteDoctors($id){
        return Favourite::where('user_id', $id)->whereNotNull('doctor_id')->get();
    }

    public function getUserFavoriteProviders($id){
        return Favourite::where('user_id', $id)->whereNotNull('provider_id')->get();
    }

    public function changerUserStatus($user, $status){
        $user = $user->update([
            'status' => $status
        ]);
        return $user;
    }

    public function getAdminPermissionsList(){
        return AdminPermission::get();
    }


}

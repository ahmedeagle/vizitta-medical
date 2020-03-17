<?php

namespace App\Http\Controllers;

/**
 * Class Crons.
 * it is a class to manage all repeated tasks like
 * Refuse delayed orders
 * ..etc.
 * @author Ahmed Emam <ahmedaboemam123@gmail.com>
 */
 use Log;
use App\Models\Reservation;
use App\Models\User;
use App\Traits\SMSTrait;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Mail;
use Carbon;
class Crons extends Controller
{
    use  SMSTrait;
    //method to prevent visiting any api link
    public function echoEmpty(){
        echo "";
    }



   public function __construct(Request $request){

            if($request->api_email != env("API_EMAIL") || $request->api_password != env("API_PASSWORD")){
                return response()->json(['message' => 'Unauthenticated.']);
            }
   }


    public function cron_job(Request $request){

         $now =   date('Y-m-d');
        //second refuse all dismissed orders
      return   $this->checkReservationsDay($now);
    }



     // get all today  reservations
    public function checkReservationsDay($now){
      //  date_default_timezone_set('Asia/Riyadh');
        $now          =   strtotime(date('Y-m-d'));
        $reservations =   Reservation::query();
      $todayReservations = $reservations -> Today()
                           -> with(['doctor' => function($q){
                                         $q->select('id', 'specification_id', DB::raw('name_' . app()->getLocale() . ' as name'))
                                       ->with(['specification' => function($q){
                                           $q->select('id', DB::raw('name_' . app()->getLocale() . ' as name'));
                                         }]);
                                          }, 'provider'
                                          ])
                         -> orderBy('day_date')->orderBy('order')
                         -> get();


                  if(isset($todayReservations) && $todayReservations -> count() > 0){
                           foreach ($todayReservations as $key => $reservation) {
                                   $this -> checkReservationTime($reservation -> id ,$reservation -> from_time,$reservation -> to_time,$reservation -> user_id,$reservation ->provider['latitude'],$reservation -> provider['longitude'] , $reservation -> branch_name,$reservation -> provider['mobile'],$reservation -> doctor['name']);
                           }
                  }
      }

     // check if now is between reservation from , to time
   protected function checkReservationTime($reservationId, $fromTime,$toTime,$userId,$branchLat,$branchLong ,$branchName,$brnachMobile,$doctorName){

          $now = Carbon\Carbon::now()->format('H:i:s');

          if ($now > $fromTime && $now < $toTime)
           {
                  $this -> checkUserInDoctorClinic($reservationId,$userId,$branchLat,$branchLong,$branchName,$brnachMobile,$doctorName);
          }
   }

     //determine if user in  or behinde clinic
   protected function checkUserInDoctorClinic($reservationId,$userId,$branchLat,$branchLong,$branchName,$brnachMobile,$doctorName){

             $user = User::find($userId);
             $distanceInKilo = -1;

             if($user){

                $distanceInKilo = $this -> getDistance($user -> latitude , $user -> longitude ,$branchLat,$branchLong );
             }


                if((int)$distanceInKilo != -1){

                       // if  arround brance only by 1 kilo or less then send notification user visit doctor
                     if((int)$distanceInKilo >= 0  &&  (int)$distanceInKilo <= 2)
                     {
                          Reservation::where('id',$reservationId) -> update(['is_visit_doctor' => 1]);

                           try {

                                   $message =   "لقد حضر  المستخدم  { $user -> name }  الحجز لدي الفرع  {$branchName} لدي الطبيب   {$doctorName} رقم  هاتف  المريض    {$user->mobile}   رقم هاتف الفرع  {$brnachMobile}";
                                $this->sendSMS('23283293239', $message);


                           } catch (Exception $e) {

                           }
                              //
                     }
                }
   }


     // get distance between user and branch
   protected function getDistance($lat1, $lon1, $lat2, $lon2, $unit = "K") {

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K") {
            return round(($miles * 1.609344));
        } else if ($unit == "N") {
            return round(($miles * 0.8684));
        } else {
            return round($miles);
        }
    }


 }

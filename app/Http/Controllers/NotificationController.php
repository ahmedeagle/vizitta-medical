<?php

namespace App\Http\Controllers;

use App\Models\AdminWebToken;
use App\Models\Notification;
use App\Models\Reciever;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Provider;
use App\Traits\GlobalTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    use GlobalTrait;
    protected $device_token;
    protected $title;
    protected $body;
    protected const API_ACCESS_KEY_PROVIDER = 'AAAAaPb2xeE:APA91bETQPIQYimxzzR9zIs-NbVcrz-AKKT1iDFqoMtJJ-Kpy57OoUvqPzo99Fcxf8D7YfWCtMOUByPESe9m74uUAvPX6dV6EDUSHQQO7qakkk_ZfZdo_Q2Zge7Ilajl9TY5U_lNfjMy';
    protected const API_ACCESS_KEY_USER = 'AAAAc1Y3kCA:APA91bGJNpIGQQo2LeIbiGzcNZQyITAbyR9zHQXkFKGifEj9cLdvaOy3n8YV8_vLzMPRrY0kUJm2634OUjApRf7PTJ4aj8PHRfZKgyy_05-0JxI7S_5AQ6IMEB9QF_HfG2fybbehpxQL';
    protected const API_ACCESS_KEY_ADMIN = 'AAAAKfLog3w:APA91bHJ2uye0C8T3v3ilKMZ0zU1siqJiWfA-mNWeRM6Gn5czwwUx65MSdGAxzyLZP0CmxVnYm1c24AhV9XX5WFvuUUcDjO8WqfUCi_32NoDGckurR4gnvLaZTMMAhZ2Yaps2hNFtqcD';
    private const fcmUrl = 'https://fcm.googleapis.com/fcm/send';

    //

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Array $data)
    {
        $this->title = $data['title'];
        $this->body = $data['body'];
    }

    public function sendUser(User $notify, $bill = false, $reservation_id = null)
    {

        $unreadNotifications = $this->getUnreadNotifications($notify);

        // $data['device_token'] = $User->device_token;
        $notification = [
            'title' => $this->title,
            'body' => $this->body,
            "click_action" => "action",
            'sound' => 'default',
            'badge' => $unreadNotifications
        ];
        if ($bill && $reservation_id != null) {
            $extraNotificationData = [
                'upload_bill' => '1',
                'reservation_id' => $reservation_id,
            ];

            // $extraNotificationData = ["message" => $notification,"moredata" =>'New Data'];
            $fcmNotification = [
                //'registration_ids' => $tokenList, //multple token array
                'to' => $notify->device_token,//'/topics/alldevices',// $User->device_token, //single token
                'notification' => $notification,
                'data' => $extraNotificationData,
            ];

        } else {
            // $extraNotificationData = ["message" => $notification,"moredata" =>'New Data'];
            //
            $fcmNotification = [
                //'registration_ids' => $tokenList, //multple token array
                'to' => $notify->device_token,//'/topics/alldevices',// $User->device_token, //single token
                'notification' => $notification,
            ];
        }

        return $this->sendFCM($fcmNotification, 'user');
    }

    public
    function sendProvider(Provider $notify)
    {
        $notification = [
            'title' => $this->title,
            'body' => $this->body,
        ];

        // $extraNotificationData = ["message" => $notification,"moredata" =>'New Data'];
        $fcmNotification = [
            //'registration_ids' => $tokenList, //multple token array
            'to' => $notify->device_token,//'/topics/alldevices',// $User->device_token, //single token
            'notification' => $notification,
        ];
        //if ($notify->device_token)
        return $this->sendFCM($fcmNotification, 'provider');
        /*  if ($notify->web_token != null)
              $this->sendProviderWebBrowser($notify);*/
    }

    public
    function sendProviderWeb(Provider $notify, $reservation_no = null, $type = 'new_reservation')
    {
        if ($reservation_no != null) {
            $notification = [
                'title' => $this->title,
                'body' => $this->body,
                "reservation_no" => $reservation_no,
                "type" => $type,
                "actor" => 'provider'
            ];
        } else {

            $notification = [
                'title' => $this->title,
                'body' => $this->body,
                "type" => $type,
                "actor" => 'provider'
            ];
        }

        $notificationData = new \stdClass();
        $notificationData->notification = $notification;
        // $extraNotificationData = ["message" => $notification,"moredata" =>'New Data'];
        $fcmNotification = [
            //'registration_ids' => $tokenList, //multple token array
            'to' => $notify->web_token,//'/topics/alldevices',// $User->device_token, //single token
            'data' => $notificationData

        ];
        return $this->sendFCM($fcmNotification, 'provider');

    }

    public
    function sendAdminWeb($type)
    {
        $notification = [
            'title' => $this->title,
            'body' => $this->body,
            "type" => $type,
            "actor" => 'admin'
        ];
        $tokenList = AdminWebToken::pluck('token')->toArray();
        $notificationData = new \stdClass();
        $notificationData->notification = $notification;
        // $extraNotificationData = ["message" => $notification,"moredata" =>'New Data'];
        $fcmNotification = [
            'registration_ids' => $tokenList,
            // 'to' => 'fqivYF6u2j2PXLlzyJSYYR:APA91bHkxvW-nq7AElRGrtvH5dR1DhggHu3YECXPzhvKMWZJ4eG0Br1ArxMgarpY5s2xS_HWF5DHobqkHZ7OcS33RGZHDS8yHUH4A963QFhj-qTd6OXMtFWFJapyRMJAl7_aebcwh288',//'/topics/alldevices',// $User->device_token, //single token
            'data' => $notificationData
        ];
        return $this->sendFCM($fcmNotification, 'admin');
    }


    /*  // weBrowser Push Format
      public function sendProviderWebBrowser(Provider $notify)
      {

          $notification = [
              'title' => $this->title,
              'body' => $this->body,
          ];

          $notificationData = new \stdClass();
          $notificationData->notification = $notification;
          // $extraNotificationData = ["message" => $notification,"moredata" =>'New Data'];
          $fcmNotification = [
              //'registration_ids' => $tokenList, //multple token array
              'to' => $notify->web_token,//'/topics/alldevices',// $User->device_token, //single token
              'data' => $notificationData

          ];


          $this->sendFCM($fcmNotification, 'provider');
      }*/


    private
    function sendFCM($fcmNotification, $type = 'user')
    {
        if ($type == 'provider') {
            $key = self::API_ACCESS_KEY_PROVIDER;
        } elseif ($type == 'admin') {
            $key = self::API_ACCESS_KEY_ADMIN;
        } else {
            $key = self::API_ACCESS_KEY_USER;
        }

        $headers = [
            'Authorization: key=' . $key,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::fcmUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public
    function setData(Array $data)
    {
        $this->device_token = $data['device_token'];
        $this->title = $data['title'];
        $this->body = $data['body'];
    }

    private function getUnreadNotifications(User $user)
    {
        $userId = $user->id;
        return Reciever::unseenForUser()->where('actor_id', $userId)->count();
    }


}

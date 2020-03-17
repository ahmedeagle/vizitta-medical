<?php

namespace App\Social;
use Carbon\Carbon;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use App\Member;

class Facebook {

    protected $helper;
    protected $accessToken;
    protected $fb;
    protected $permissions = [
        'public_profile',
        'email'
              ]; // optional

    /*
      notice all premissions provided:
      $premissions = ['public_profile',
      'user_friends',
      'email',
      'user_about_me',
      'user_actions.books',
      'user_actions.fitness',
      'user_actions.music',
      'user_actions.news',
      'user_actions.video',
      'user_actions:{app_namespace}',
      'user_birthday',
      'user_education_history',
      'user_events',
      'user_games_activity',
      'user_hometown',
      'user_likes',
      'user_location',
      'user_managed_groups',
      'user_photos',
      'user_posts',
      'user_relationships',
      'user_relationship_details',
      'user_religion_politics',
      'user_tagged_places',
      'user_videos',
      'user_website',
      'user_work_history',
      'read_custom_friendlists',
      'read_insights',
      'read_audience_network_insights',
      'read_page_mailboxes',
      'manage_pages',
      'publish_pages',
      'publish_actions',
      'rsvp_event',
      'pages_show_list',
      'pages_manage_cta',
      'pages_manage_instant_articles',
      'ads_read',
      'ads_management',
      'pages_messaging',
      'pages_messaging_phone_number',];
     */

    function __construct() {
        $this->fb = new \Facebook\Facebook([
            'app_id' => config('services.social.facebook.app_id'),
            'app_secret' => config('services.social.facebook.app_secret'),
            'default_graph_version' => config('services.social.facebook.default_graph_version'),
             'persistent_data_handler'=>'session',
        ]);

        $this->helper = $this->fb->getRedirectLoginHelper();
    }

    public function getLoginUrl() {
        return $this->helper->getLoginUrl(route('login.facebook'), $this->permissions);

    }

    public function setAccessToken() {
        try {
            $this->accessToken = $this->helper->getAccessToken();
        } catch (FacebookResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch (FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }
    }

    public function updateUserInformation() {

        if (isset($this->accessToken)) {

            try {
                // Returns a `Facebook\FacebookResponse` object
                $res1 = $this->fb->get('/me/picture?type=large&redirect=false', $this->accessToken->getValue());
                $res2 = $this->fb->get('/me?fields=id,name,email,gender,cover,location', $this->accessToken->getValue());
            } catch (FacebookResponseException $e) {
                echo 'Graph returned an error: ' . $e->getMessage();
                exit;
            } catch (FacebookSDKException $e) {
                echo 'Facebook SDK returned an error: ' . $e->getMessage();
                exit;
            }

            // Fetched by the Graph API
            $picture = $res1->getGraphUser();
            $details = $res2->getGraphUser();

            // Fetching the fields
            $email = $details->getProperty('email');
            if (!$this->isUserExist($email)) {

                // $id = $details->getProperty('id');
                // $gender = $details->getProperty('gender');
                // $cover = $details->getProperty('cover')->getField('source');
                $image = $picture->getProperty('url');
                //$address = $details->getProperty('location')->getField('name');
                $name = $details->getProperty('name');
                $name = explode(' ', $name);
                $f_name = $name[0];
                $l_name = end($name);

                $user = new Member();
                $user->f_name = $f_name;
                $user->l_name = $l_name;
                $user->email = $email;
                $user->image = $image;
                $user->password='facebook';
                $user->address='italy';
                $user->auth_type='facebook';
                //$user->address = $address;
                $user->save();

                auth()->guard('members')->login($user);
                 return true;
            }
            else {
                $user = Member::where('email',$email)->first();
                auth()->guard('members')->login($user);
                return true;
            }
        }
        return false;
    }

    protected function isUserExist($email){
        return (bool) Member::where('email',$email)->first();
    }


}

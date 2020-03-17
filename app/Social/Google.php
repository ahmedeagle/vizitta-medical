<?php

namespace App\Social;
use Carbon\Carbon;
use Google_Client;
use Google_Service_Plus;
use App\Member;

class Google {
	protected $payload;
    protected $client;

    public function __construct() {

            $this->client = new Google_Client;
            $this->client->setClientId(config('services.social.google.client_id'));
            $this->client->setClientSecret(config('services.social.google.client_secret'));
            $this->client->setRedirectUri(route('login.google'));
            $this->client->setScopes(config('services.social.google.scopes'));
    }

    public function getLoginUrl() {
        return $this->client->createAuthUrl();
    }

    public function updateUserInformation() {
        if (request()->has('code')) {
            $this->client->authenticate(request()->get('code'));
            $this->setToken($this->client->getAccessToken());
            $plus = new Google_Service_Plus($this->client);
            $person = $plus->people->get('me');


            $email = $person['emails'][0]['value'];
            if (!$this->isUserExist($email)) {

                //fetching user information
                $id = $person['id'];
                $name = $person['displayName'];
                $image = $person['image']['url'];
                //$cover = $person['modelData']['cover']['coverPhoto']['url'];
                //$gender = $person['gender'];
                //$address = $person['modelData']['placesLived'][0]['value'];
                //$company = $person['modelData']['organizations'][0]['name'];

                // Fetched normaliy
                $name = explode(' ', $name);
                $f_name = $name[0];
                $l_name = end($name);



                $user = new Member();
                $user->f_name = $f_name;
                $user->l_name = $l_name;
                $user->email = $email;
                $user->image = $image;
                $user->password='google';
                $user->address='italy';
                $user->auth_type='google';
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
    private function setToken($token) {
        $this->client->setAccessToken($token);
    }

}

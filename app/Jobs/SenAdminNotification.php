<?php

namespace App\Jobs;

use App\Models\Provider;
use App\Models\Reciever;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SenAdminNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $timeout = 240;

    public $actors;
    public $type;
    public $notify_id;
    public $title;
    public $content;
    public $allow_fire_base;

     public function __construct($actors, $notify_id, $content, $title, $type,$allow_fire_base)
    {
        $this->actors = $actors;
        $this->type = $type;
        $this->notify_id = $notify_id;
        $this->title = $title;
        $this->content = $content;
        $this->allow_fire_base = $allow_fire_base;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->actors as $actor) {


            $actor->makeVisible(['device_token', 'web_token']);
            if ($this->type == "users") {

                Reciever::insert([
                    "notification_id" => $this->notify_id,
                    "actor_id" => $actor->id,
                    "actor_type" => $this->type
                ]);
                // push notification
                if ($actor->device_token != null && $this->allow_fire_base == 1) {
                    //send push notification
                    (new \App\Http\Controllers\NotificationController(['title' => $this->title, 'body' => $this -> content]))->sendUser(User::find($actor->id));
                }

            } elseif ($this -> type == "providers") {

                Reciever::insert([
                    "notification_id" => $this -> notify_id,
                    "actor_id" => $actor->id,
                    "actor_type" => $this->type

                ]);
                // push notification
                if ($actor->device_token != null &&  $this->allow_fire_base == 1) {
                    (new \App\Http\Controllers\NotificationController(['title' => $this -> title, 'body' => $this -> content]))->sendProvider(Provider::find($actor->id));
                }

                if ($actor->web_token != null && $this->allow_fire_base == 1) {
                    (new \App\Http\Controllers\NotificationController(['title' => $this -> title, 'body' => $this -> content]))->sendProviderWeb(Provider::find($actor->id));
                }

            }
        }
    }
}

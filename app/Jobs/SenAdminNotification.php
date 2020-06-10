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

    public function __construct($actors, $notify_id, $content, $title, $type)
    {
        $this->actors = $actors;
        $this->type = $type;
        $this->notify_id = $notify_id;
        $this->title = $title;
        $this->content = $content;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->actors as $actor) {

            Reciever::insert([
                "notification_id" => $this->notify_id,
                "actor_id" => $actor->id,
                "device" => $this-> type .''.$this -> title. ''.$this ->  content . ''.$this -> notify_id
            ]);

            $actor->makeVisible(['device_token', 'web_token']);
            if ($this->type == "users") {

                Reciever::insert([
                    "notification_id" => $this->notify_id,
                    "actor_id" => $actor->id
                ]);
                // push notification
                if ($actor->device_token != null) {
                    //send push notification
                    (new \App\Http\Controllers\NotificationController(['title' => $this->title, 'body' => $this -> content]))->sendUser(User::find($actor->id));
                }

            } elseif ($this -> type == "providers") {

                Reciever::insert([
                    "notification_id" => $this -> notify_id,
                    "actor_id" => $actor->id,

                ]);
                // push notification
                if ($actor->device_token != null) {
                    (new \App\Http\Controllers\NotificationController(['title' => $this -> title, 'body' => $this -> content]))->sendProvider(Provider::find($actor->id));
                }

                if ($actor->web_token != null) {
                    (new \App\Http\Controllers\NotificationController(['title' => $this -> title, 'body' => $this -> content]))->sendProviderWeb(Provider::find($actor->id));
                }

            }
        }
    }
}

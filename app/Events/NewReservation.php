<?php

namespace App\Events;

use App\Models\Replay;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Str;

class NewReservation implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $title;
    public $content;
    public $date;
    public $time;
    public $photo;
    public $path;
    public $reservation_no;
    public $reservation_id;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($notification = [])
    {

        $this->title = $notification['provider_name'] . 'حجز جديد لدي مقدم الخدمة  ';
        $this->content = Str::limit($notification['content'], 70);
        $this->date = date("Y M d", strtotime(Carbon::now()));
        $this->time = date("h:i A", strtotime(Carbon::now()));
        $this->photo = $notification['photo'];
        $this->reservation_no = $notification['reservation_no'];
        $this->reservation_id = $notification['reservation_id'];
        $this->path = route('admin.reservation.view', $notification['reservation_id']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return ['new-reservation'];
    }
}

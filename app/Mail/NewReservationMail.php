<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewReservationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $provider_name;
    protected $day_name;
    protected $day_date;
    protected $from_time;
    protected $to_time;

    public function __construct($provider_name, $day_name, $day_date, $from_time, $to_time)
    {
        $this->provider_name = $provider_name;
        $this->day_name = $day_name;
        $this->day_date = $day_date;
        $this->from_time = $from_time;
        $this->to_time = $to_time;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(env('MAIL_MAIL'), trans('messages.Health Station'))
            ->subject(trans('messages.New Reservation'))
            ->view('emails.newReservation')
            ->with([
                'provider_name' => $this->provider_name,
                'day_name' => $this->day_name,
                'day_date' => $this->day_date,
                'from_time' => $this->from_time,
                'to_time' => $this->to_time,
            ]);
    }
}

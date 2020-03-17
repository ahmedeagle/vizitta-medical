<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AcceptReservationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $reservation_no;

    public function __construct($reservation_no)
    {
        $this->reservation_no = $reservation_no;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(env('MAIL_MAIL'), trans('messages.Health Station'))
            ->subject('قبول الحجز')
            ->view('emails.acceptReservation')
            ->with([
                'reservation_no' => $this->reservation_no
            ]);
    }
}

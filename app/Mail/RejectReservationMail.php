<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class RejectReservationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $reservation_no;
    protected $reason;

    public function __construct($reservation_no, $reason)
    {
        $this->reservation_no = $reservation_no;
        $this->reason = $reason;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(env('MAIL_MAIL'), trans('messages.Health Station'))
            ->subject('رفض الحجز')
            ->view('emails.rejectReservation')
            ->with([
                'reservation_no' => $this->reservation_no,
                'reason' => $this->reason
            ]);
    }
}

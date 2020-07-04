<?php

namespace App\Console\Commands;

use App\Models\DoctorConsultingReservation;
 use App\Traits\SMSTrait;
 use Illuminate\Console\Command;

class SendDoctorSMS extends Command
{
    use SMSTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'doctor:sms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send message to consulting doctor before reseration time with 5 minutes ';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $reservations = DoctorConsultingReservation::with(['doctor' => function ($q) {
            $q->select('id', 'phone', 'name_ar');
        }])
            ->where('approved', '0')
            ->where('notified',0)
            ->get();    // get all new reservations


        if (isset($reservations) && $reservations->count() > 0) {
            foreach ($reservations as $key => $consulting) {
                DoctorConsultingReservation::where('id', $consulting->id)->update(['remaining_price' => 11]);
                $consulting_start_date = date('Y-m-d H:i:s', strtotime($consulting->day_date . ' ' . $consulting->from_time));
                if ($consulting_start_date > date('Y-m-d H:i:s')) {
                    if (getDiffBetweenTwoDateIMinute(date('Y-m-d H:i:s'), $consulting_start_date) <= 5) {
                        DoctorConsultingReservation::where('id', $consulting->id)->update(['notified' => 1]);

                         //send sms to consulting doctor
                        $doctorMessage = 'هناك حجز استشارة جديد بعد 5 دقائق من الان  برقم ' . ' ' . $consulting->reservation_no . ' ' . ' ( ' . $consulting->doctor->name_ar . ' )';
                        if (!is_null($consulting->doctor->phone))
                            $this->sendSMS($consulting->doctor->phone, $doctorMessage);  //sms for doctor
                    }

                }
            }
        }
    }
}

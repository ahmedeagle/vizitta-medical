<?php

namespace App\Console\Commands;

use App\Models\DoctorConsultingReservation;
use App\Models\FeaturedBranch;
use App\Models\PromoCodeCategory;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ConsultingReservationExpire extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'consulting:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire all Consulting reservation in pending and approved if time passed without start the chat ';


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

        $reservations = DoctorConsultingReservation::with(['user' => function ($q) {
            $q->select('id', 'name', 'photo');
        }])
            ->wherein('approved',['0','1'])
            ->get();


            // if time passed  without close chat and reservation close it automatically
            if (isset($reservations) && $reservations->count() > 0) {
                foreach ($reservations as $key => $consulting) {
                    $consulting_start_date = date('Y-m-d H:i:s', strtotime($consulting->day_date . ' ' . $consulting->from_time));
                    $consulting_end_date = date('Y-m-d H:i:s', strtotime($consulting->day_date . ' ' . $consulting->to_time));
                    $currentDate = date('Y-m-d H:i:s');

                    if (date('Y-m-d H:i:s') >= $consulting_end_date) {
                        $consulting->approved = '3';
                        DoctorConsultingReservation::where('id', $consulting->id)->update(['approved' => '3']);
                    }
                }

        }

    }
}
